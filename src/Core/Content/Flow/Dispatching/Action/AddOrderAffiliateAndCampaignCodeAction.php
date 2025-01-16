<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching\Action;

use Cicada\Core\Content\Flow\Dispatching\DelayableAction;
use Cicada\Core\Content\Flow\Dispatching\StorableFlow;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Event\OrderAware;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('after-sales')]
class AddOrderAffiliateAndCampaignCodeAction extends FlowAction implements DelayableAction
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $orderRepository
    ) {
    }

    public static function getName(): string
    {
        return 'action.add.order.affiliate.and.campaign.code';
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
     * @return array<mixed>
     */
    private function getAffiliateAndCampaignCodeFromOrderId(string $orderId): array
    {
        $data = $this->connection->fetchAssociative(
            'SELECT affiliate_code, campaign_code FROM `order` WHERE id = :id',
            [
                'id' => Uuid::fromHexToBytes($orderId),
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
    private function update(Context $context, array $config, string $orderId): void
    {
        if (!\array_key_exists('affiliateCode', $config) || !\array_key_exists('campaignCode', $config)) {
            return;
        }

        $orderData = $this->getAffiliateAndCampaignCodeFromOrderId($orderId);

        if (empty($orderData)) {
            return;
        }

        $affiliateCode = $orderData['affiliate_code'];
        if ($affiliateCode === null || $config['affiliateCode']['upsert']) {
            $affiliateCode = $config['affiliateCode']['value'];
        }

        $campaignCode = $orderData['campaign_code'];
        if ($campaignCode === null || $config['campaignCode']['upsert']) {
            $campaignCode = $config['campaignCode']['value'];
        }

        $data = [];
        if ($affiliateCode !== $orderData['affiliate_code']) {
            $data['affiliateCode'] = $affiliateCode;
        }

        if ($campaignCode !== $orderData['campaign_code']) {
            $data['campaignCode'] = $campaignCode;
        }

        if (empty($data)) {
            return;
        }

        $data['id'] = $orderId;

        $this->orderRepository->update([$data], $context);
    }
}
