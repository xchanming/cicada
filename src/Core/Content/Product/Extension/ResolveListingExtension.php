<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Extension;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Extensions\Extension;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @experimental stableVersion:v6.7.0 feature:EXTENSION_SYSTEM
 *
 * @codeCoverageIgnore
 *
 * @extends Extension<EntitySearchResult<ProductCollection>>
 */
#[Package('inventory')]
final class ResolveListingExtension extends Extension
{
    public const NAME = 'listing-loader.resolve';

    /**
     * @internal cicada owns the __constructor, but the properties are public API
     */
    public function __construct(
        /**
         * @public
         *
         * @description The criteria which should be used to load the products. Is also containing the selected customer filter
         */
        public readonly Criteria $criteria,
        /**
         * @public
         *
         * @description Allows you to access to the current customer/sales-channel context
         */
        public readonly SalesChannelContext $context
    ) {
    }
}
