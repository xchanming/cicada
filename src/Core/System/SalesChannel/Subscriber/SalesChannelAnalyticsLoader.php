<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\Aggregate\SalesChannelAnalytics\SalesChannelAnalyticsEntity;
use Cicada\Storefront\Event\StorefrontRenderEvent;

/**
 * @internal
 */
#[Package('buyers-experience')]
class SalesChannelAnalyticsLoader
{
    public function __construct(
        private readonly EntityRepository $salesChannelAnalyticsRepository,
    ) {
    }

    public function loadAnalytics(StorefrontRenderEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();
        $salesChannel = $salesChannelContext->getSalesChannel();
        $analyticsId = $salesChannel->getAnalyticsId();

        if (empty($analyticsId)) {
            return;
        }

        $criteria = new Criteria([$analyticsId]);
        $criteria->setTitle('sales-channel::load-analytics');

        /** @var SalesChannelAnalyticsEntity|null $analytics */
        $analytics = $this->salesChannelAnalyticsRepository->search($criteria, $salesChannelContext->getContext())->first();

        $event->setParameter('storefrontAnalytics', $analytics);
    }
}
