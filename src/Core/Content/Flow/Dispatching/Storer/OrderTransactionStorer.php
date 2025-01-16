<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Content\Flow\Dispatching\Aware\OrderTransactionAware;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('after-sales')]
class OrderTransactionStorer extends FlowStorer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param array<string, mixed> $stored
     *
     * @return array<string, mixed>
     */
    public function store(FlowEventAware $event, array $stored): array
    {
        if (!$event instanceof OrderTransactionAware || isset($stored[OrderTransactionAware::ORDER_TRANSACTION_ID])) {
            return $stored;
        }

        $stored[OrderTransactionAware::ORDER_TRANSACTION_ID] = $event->getOrderTransactionId();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(OrderTransactionAware::ORDER_TRANSACTION_ID)) {
            return;
        }

        $storable->lazy(
            OrderTransactionAware::ORDER_TRANSACTION,
            $this->lazyLoad(...)
        );
    }

    private function lazyLoad(StorableFlow $storableFlow): ?OrderTransactionEntity
    {
        $id = $storableFlow->getStore(OrderTransactionAware::ORDER_TRANSACTION_ID);
        if ($id === null) {
            return null;
        }

        $criteria = new Criteria([$id]);

        return $this->loadOrderTransaction($criteria, $storableFlow->getContext(), $id);
    }

    private function loadOrderTransaction(Criteria $criteria, Context $context, string $id): ?OrderTransactionEntity
    {
        $event = new BeforeLoadStorableFlowDataEvent(
            OrderTransactionDefinition::ENTITY_NAME,
            $criteria,
            $context,
        );

        $this->dispatcher->dispatch($event, $event->getName());

        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->get($id);

        if ($orderTransaction) {
            /** @var OrderTransactionEntity $orderTransaction */
            return $orderTransaction;
        }

        return null;
    }
}
