<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Storer;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CustomerGroupAware;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('after-sales')]
class CustomerGroupStorer extends FlowStorer
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $customerGroupRepository,
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
        if (!$event instanceof CustomerGroupAware || isset($stored[CustomerGroupAware::CUSTOMER_GROUP_ID])) {
            return $stored;
        }

        $stored[CustomerGroupAware::CUSTOMER_GROUP_ID] = $event->getCustomerGroupId();

        return $stored;
    }

    public function restore(StorableFlow $storable): void
    {
        if (!$storable->hasStore(CustomerGroupAware::CUSTOMER_GROUP_ID)) {
            return;
        }

        $storable->setData(CustomerGroupAware::CUSTOMER_GROUP_ID, $storable->getStore(CustomerGroupAware::CUSTOMER_GROUP_ID));

        $storable->lazy(
            CustomerGroupAware::CUSTOMER_GROUP,
            $this->lazyLoad(...)
        );
    }

    private function lazyLoad(StorableFlow $storableFlow): ?CustomerGroupEntity
    {
        $id = $storableFlow->getStore(CustomerGroupAware::CUSTOMER_GROUP_ID);
        if ($id === null) {
            return null;
        }

        $criteria = new Criteria([$id]);

        return $this->loadCustomerGroup($criteria, $storableFlow->getContext(), $id);
    }

    private function loadCustomerGroup(Criteria $criteria, Context $context, string $id): ?CustomerGroupEntity
    {
        $event = new BeforeLoadStorableFlowDataEvent(
            CustomerGroupDefinition::ENTITY_NAME,
            $criteria,
            $context,
        );

        $this->dispatcher->dispatch($event, $event->getName());

        $customerGroup = $this->customerGroupRepository->search($criteria, $context)->get($id);

        if ($customerGroup) {
            /** @var CustomerGroupEntity $customerGroup */
            return $customerGroup;
        }

        return null;
    }
}
