<?php declare(strict_types=1);

namespace Cicada\Core\System\NumberRange\Aggregate\NumberRangeSalesChannel;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<NumberRangeSalesChannelEntity>
 */
#[Package('checkout')]
class NumberRangeSalesChannelCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'number_range_sales_channel_collection';
    }

    protected function getExpectedClass(): string
    {
        return NumberRangeSalesChannelEntity::class;
    }
}
