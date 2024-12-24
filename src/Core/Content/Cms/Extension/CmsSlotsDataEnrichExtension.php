<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Extension;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Cicada\Core\Content\Cms\DataResolver\CriteriaCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Extensions\Extension;
use Cicada\Core\Framework\Log\Package;

/**
 * @public This class is used as type-hint for all event listeners, so the class string is "public consumable" API
 *
 * @title Enrich the CMS slots with the loaded data from the search results
 *
 * @description This event allows interception of the enrichment process,
 * during which CMS slots used in a rendered CMS page are populated with data loaded by the respective CMS resolver from the search results.
 *
 * @template TEntityCollection of EntityCollection
 *
 * @experimental stableVersion:v6.7.0 feature:EXTENSION_SYSTEM
 *
 * @codeCoverageIgnore
 *
 * @extends Extension<CmsSlotCollection>
 */
#[Package('buyers-experience')]
final class CmsSlotsDataEnrichExtension extends Extension
{
    public const NAME = 'cms-slots-data.enrich';

    /**
     * @internal Cicada owns the __constructor, but the properties are public API
     */
    public function __construct(
        /**
         * @public
         *
         * @description The slot collection which will be enriched with the data of the identifier and criteria results
         */
        public readonly CmsSlotCollection $slots,
        /**
         * @public
         *
         * @description The criteria list which is used for the mapping of the search results
         *
         * @var array<string, CriteriaCollection>
         */
        public readonly array $criteriaList,
        /**
         * @public
         *
         * @description The fetched slot data which was searched by the identifiers
         *
         * @var array<EntitySearchResult<TEntityCollection>>
         */
        public readonly array $identifierResult,
        /**
         * @public
         *
         * @description The fetched slot data which was searched by the criteria list
         *
         * @var array<EntitySearchResult<TEntityCollection>>
         */
        public readonly array $criteriaResult,
        /**
         * @public
         *
         * @description Allows you to access to the current resolver-context
         */
        public readonly ResolverContext $resolverContext,
    ) {
    }
}
