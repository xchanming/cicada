<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('services-settings')]
class SetCustomerCustomFieldAction extends FlowAction implements DelayableAction
{
    use CustomFieldActionTrait;

    /**
     * @internal
     *
     * @param EntityRepository<CustomerCollection> $customerRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $customerRepository
    ) {
    }

    public static function getName(): string
    {
        return 'action.set.customer.custom.field';
    }

    /**
     * @return array<int, string>
     */
    public function requirements(): array
    {
        return [CustomerAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasData(CustomerAware::CUSTOMER_ID)) {
            return;
        }

        $this->update($flow->getContext(), $flow->getConfig(), $flow->getData(CustomerAware::CUSTOMER_ID));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function update(Context $context, array $config, string $customerId): void
    {
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context)->getEntities()->first();

        $customFields = $this->getCustomFieldForUpdating($customer?->getCustomFields(), $config);
        if ($customFields === null) {
            return;
        }

        $customFields = empty($customFields) ? null : $customFields;

        $this->customerRepository->update([
            [
                'id' => $customerId,
                'customFields' => $customFields,
            ],
        ], $context);
    }
}
