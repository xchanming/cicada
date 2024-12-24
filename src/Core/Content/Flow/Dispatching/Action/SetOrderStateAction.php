<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Doctrine\DBAL\Connection;
use Cicada\Core\Checkout\Order\SalesChannel\OrderService;
use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Dispatching\TransactionalAction;
use Cicada\Core\Content\Flow\Dispatching\TransactionFailedException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\StateMachine\Exception\IllegalTransitionException;
use Cicada\Core\System\StateMachine\StateMachineException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @internal
 */
#[Package('services-settings')]
class SetOrderStateAction extends FlowAction implements DelayableAction, TransactionalAction
{
    final public const FORCE_TRANSITION = 'force_transition';

    private const ORDER = 'order';

    private const ORDER_DELIVERY = 'order_delivery';

    private const ORDER_TRANSACTION = 'order_transaction';

    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly OrderService $orderService
    ) {
    }

    public static function getName(): string
    {
        return 'action.set.order.state';
    }

    /**
     * @return array<int, string>
     */
    public function requirements(): array
    {
        return [OrderAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasData(OrderAware::ORDER_ID)) {
            return;
        }

        $this->update($flow->getContext(), $flow->getConfig(), $flow->getData(OrderAware::ORDER_ID));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function update(Context $context, array $config, string $orderId): void
    {
        if (empty($config)) {
            return;
        }

        if ($config[self::FORCE_TRANSITION] ?? false) {
            $context->addState(self::FORCE_TRANSITION);
        }

        $transitions = array_filter([
            self::ORDER => $config[self::ORDER] ?? null,
            self::ORDER_DELIVERY => $config[self::ORDER_DELIVERY] ?? null,
            self::ORDER_TRANSACTION => $config[self::ORDER_TRANSACTION] ?? null,
        ]);

        try {
            foreach ($transitions as $machine => $toPlace) {
                $this->transitState((string) $machine, $orderId, (string) $toPlace, $context);
            }
        } catch (StateMachineException $e) {
            throw TransactionFailedException::because($e);
        } finally {
            $context->removeState(self::FORCE_TRANSITION);
        }
    }

    /**
     * @throws IllegalTransitionException
     * @throws StateMachineException
     */
    private function transitState(string $machine, string $orderId, string $toPlace, Context $context): void
    {
        if (!$toPlace) {
            return;
        }

        $data = new ParameterBag();
        if ($machine === self::ORDER) {
            $machineId = $orderId;
        } elseif ($machine === self::ORDER_DELIVERY) {
            $machineId = $this->getMachineIdFromOrderDelivery($orderId);
        } else {
            $machineId = $this->getMachineId($machine, $orderId);
        }

        if (!$machineId) {
            throw StateMachineException::stateMachineNotFound($machine);
        }

        $actionName = $this->getAvailableActionName($machine, $machineId, $toPlace);
        if (!$actionName) {
            $actionName = $toPlace;
        }

        switch ($machine) {
            case self::ORDER:
                $this->orderService->orderStateTransition($orderId, $actionName, $data, $context);

                return;
            case self::ORDER_DELIVERY:
                $this->orderService->orderDeliveryStateTransition($machineId, $actionName, $data, $context);

                return;
            case self::ORDER_TRANSACTION:
                $this->orderService->orderTransactionStateTransition($machineId, $actionName, $data, $context);

                return;
            default:
                throw StateMachineException::stateMachineNotFound($machine);
        }
    }

    private function getMachineId(string $machine, string $orderId): ?string
    {
        return $this->connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM ' . $machine . ' WHERE order_id = :id AND version_id = :version ORDER BY created_at DESC',
            [
                'id' => Uuid::fromHexToBytes($orderId),
                'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ]
        ) ?: null;
    }

    /**
     * Returns the Id of the primary delivery of the given order.
     * This is the order delivery with the highest shipping costs as Cicada creates additional order deliveries with
     * negative shipping costs when applying a discount to the shipping costs. Just using the first or last order delivery
     * without sorting first can result in the wrong order delivery to be used.
     */
    private function getMachineIdFromOrderDelivery(string $orderId): ?string
    {
        return $this->connection->fetchOne(
            'SELECT LOWER(HEX(id)) FROM ' . self::ORDER_DELIVERY . ' WHERE order_id = :id AND version_id = :version ORDER BY JSON_EXTRACT(shipping_costs, \'$.totalPrice\') DESC',
            [
                'id' => Uuid::fromHexToBytes($orderId),
                'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ],
        ) ?: null;
    }

    private function getAvailableActionName(string $machine, string $machineId, string $toPlace): ?string
    {
        $actionName = $this->connection->fetchOne(
            'SELECT action_name FROM state_machine_transition WHERE from_state_id = :fromStateId AND to_state_id = :toPlaceId',
            [
                'fromStateId' => $this->getFromPlaceId($machine, $machineId),
                'toPlaceId' => $this->getToPlaceId($toPlace, $machine),
            ]
        );

        return $actionName ?: null;
    }

    private function getToPlaceId(string $toPlace, string $machine): ?string
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM state_machine_state WHERE technical_name = :toPlace AND state_machine_id = :stateMachineId',
            [
                'toPlace' => $toPlace,
                'stateMachineId' => $this->getStateMachineId($machine),
            ]
        );

        return $id ?: null;
    }

    private function getFromPlaceId(string $machine, string $machineId): ?string
    {
        $escaped = EntityDefinitionQueryHelper::escape($machine);
        $id = $this->connection->fetchOne(
            'SELECT state_id FROM ' . $escaped . 'WHERE id = :id AND version_id = :version',
            [
                'id' => Uuid::fromHexToBytes($machineId),
                'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ]
        );

        return $id ?: null;
    }

    private function getStateMachineId(string $machine): ?string
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM state_machine WHERE technical_name = :technicalName',
            [
                'technicalName' => $machine . '.state',
            ]
        );

        return $id ?: null;
    }
}
