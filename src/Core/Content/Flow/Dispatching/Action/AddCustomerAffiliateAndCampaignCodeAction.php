<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Event\CustomerAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('after-sales')]
class AddCustomerAffiliateAndCampaignCodeAction extends FlowAction implements DelayableAction
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $customerRepository
    ) {
    }

    public static function getName(): string
    {
        return 'action.add.customer.affiliate.and.campaign.code';
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
     * @return array<mixed>
     */
    private function getAffiliateAndCampaignCodeFromCustomerId(string $customerId): array
    {
        $data = $this->connection->fetchAssociative(
            'SELECT affiliate_code, campaign_code FROM customer WHERE id = :id',
            [
                'id' => Uuid::fromHexToBytes($customerId),
            ]
        );

        if (!$data) {
            return [];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function update(Context $context, array $config, string $customerId): void
    {
        if (!\array_key_exists('affiliateCode', $config) || !\array_key_exists('campaignCode', $config)) {
            return;
        }

        $customerData = $this->getAffiliateAndCampaignCodeFromCustomerId($customerId);
        if (empty($customerData)) {
            return;
        }

        $affiliateCode = $customerData['affiliate_code'];
        if ($affiliateCode === null || $config['affiliateCode']['upsert']) {
            $affiliateCode = $config['affiliateCode']['value'];
        }

        $campaignCode = $customerData['campaign_code'];
        if ($campaignCode === null || $config['campaignCode']['upsert']) {
            $campaignCode = $config['campaignCode']['value'];
        }

        $data = [];
        if ($affiliateCode !== $customerData['affiliate_code']) {
            $data['affiliateCode'] = $affiliateCode;
        }

        if ($campaignCode !== $customerData['campaign_code']) {
            $data['campaignCode'] = $campaignCode;
        }

        if (empty($data)) {
            return;
        }

        $data['id'] = $customerId;

        $this->customerRepository->update([$data], $context);
    }
}
