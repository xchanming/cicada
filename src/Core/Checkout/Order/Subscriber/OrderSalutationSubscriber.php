<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Subscriber;

use Cicada\Core\Checkout\Order\OrderEvents;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class OrderSalutationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_ADDRESS_WRITTEN_EVENT => 'setDefaultSalutation',
            OrderEvents::ORDER_CUSTOMER_WRITTEN_EVENT => 'setDefaultSalutation',
        ];
    }

    public function setDefaultSalutation(EntityWrittenEvent $event): void
    {
        $payloads = $event->getPayloads();
        foreach ($payloads as $payload) {
            if (\array_key_exists('salutationId', $payload) && $payload['salutationId']) {
                continue;
            }

            if (!isset($payload['id'])) {
                continue;
            }

            $this->updateOrderAddressWithNotSpecifiedSalutation($payload['id']);
        }
    }

    private function updateOrderAddressWithNotSpecifiedSalutation(string $id): void
    {
        $this->connection->executeStatement(
            '
                UPDATE `order_address`
                SET `salutation_id` = (
                    SELECT `id`
                    FROM `salutation`
                    WHERE `salutation_key` = :notSpecified
                    LIMIT 1
                )
                WHERE `id` = :id AND `salutation_id` is NULL
            ',
            ['id' => Uuid::fromHexToBytes($id), 'notSpecified' => SalutationDefinition::NOT_SPECIFIED]
        );
    }
}
