<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Cicada\Core\Framework\Log\Package;

/**
 * @final
 */
#[Package('inventory')]
class ProductAvailableFilter extends MultiFilter
{
    public function __construct(
        string $salesChannelId,
        int $visibility = ProductVisibilityDefinition::VISIBILITY_ALL
    ) {
        parent::__construct(
            self::CONNECTION_AND,
            [
                new RangeFilter('product.visibilities.visibility', [RangeFilter::GTE => $visibility]),
                new EqualsFilter('product.visibilities.salesChannelId', $salesChannelId),
                new EqualsFilter('product.active', true),
            ]
        );
    }
}
