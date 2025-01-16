<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupCollection;
use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CustomerGroupAware;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('after-sales')]
class SetCustomerGroupCustomFieldAction extends FlowAction implements DelayableAction
{
    use CustomFieldActionTrait;

    /**
     * @internal
     *
     * @param EntityRepository<CustomerGroupCollection> $customerGroupRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $customerGroupRepository
    ) {
    }

    public static function getName(): string
    {
        return 'action.set.customer.group.custom.field';
    }

    /**
     * @return array<int, string>
     */
    public function requirements(): array
    {
        return [CustomerGroupAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasData(CustomerGroupAware::CUSTOMER_GROUP_ID)) {
            return;
        }

        $this->update($flow->getContext(), $flow->getConfig(), $flow->getData(CustomerGroupAware::CUSTOMER_GROUP_ID));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function update(Context $context, array $config, string $customerGroupId): void
    {
        $customerGroup = $this->customerGroupRepository->search(new Criteria([$customerGroupId]), $context)->getEntities()->first();

        $customFields = $this->getCustomFieldForUpdating($customerGroup?->getCustomFields(), $config);
        if ($customFields === null) {
            return;
        }

        $customFields = empty($customFields) ? null : $customFields;

        $this->customerGroupRepository->update([
            [
                'id' => $customerGroupId,
                'customFields' => $customFields,
            ],
        ], $context);
    }
}
