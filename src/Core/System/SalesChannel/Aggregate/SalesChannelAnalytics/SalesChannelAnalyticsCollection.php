<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Aggregate\SalesChannelAnalytics;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SalesChannelAnalyticsEntity>
 */
#[Package('discovery')]
class SalesChannelAnalyticsCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'sales_channel_analytics_collection';
    }

    protected function getExpectedClass(): string
    {
        return SalesChannelAnalyticsEntity::class;
    }
}
