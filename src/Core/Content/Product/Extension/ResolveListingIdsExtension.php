<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Extension;

use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Extensions\Extension;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @public this class is used as type-hint for all event listeners, so the class string is "public consumable" API
 *
 * @title Determination of the listing product ids
 *
 * @description This event allows intercepting the listing process, when the product ids should be determined for the current category page and the applied filter.
 *
 * @experimental stableVersion:v6.7.0 feature:EXTENSION_SYSTEM
 *
 * @codeCoverageIgnore
 *
 * @extends Extension<IdSearchResult>
 */
#[Package('inventory')]
final class ResolveListingIdsExtension extends Extension
{
    public const NAME = 'listing-loader.resolve-listing-ids';

    /**
     * @internal cicada owns the __constructor, but the properties are public API
     */
    public function __construct(
        /**
         * @public
         *
         * @description The criteria which should be used to load the product ids. Is also containing the selected customer filter
         */
        public Criteria $criteria,

        /**
         * @public
         *
         * @description Allows you to access to the current customer/sales-channel context
         */
        public SalesChannelContext $context
    ) {
    }
}
