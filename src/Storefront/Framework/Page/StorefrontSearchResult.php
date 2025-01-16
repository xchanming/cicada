<?php declare(strict_types=1);

namespace Cicada\Storefront\Framework\Page;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

/**
 * @template TEntityCollection of EntityCollection
 *
 * @template-extends EntitySearchResult<TEntityCollection>
 *
 * @deprecated tag:v6.7.0 - will be removed without replacement use `EntitySearchResult` instead, all methods are now contained in the `EntitySearchResult` and the sorting was not in use anymore
 */
#[Package('framework')]
class StorefrontSearchResult extends EntitySearchResult
{
    /**
     * @var array<FieldSorting>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $sortings = [];

    /**
     * @var string|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $sorting;

    /**
     * @return array<FieldSorting>
     */
    public function getSortings(): array
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            '\Cicada\Storefront\Framework\Page\StorefrontSearchResult will be removed use \Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult instead'
        );

        return $this->sortings;
    }

    /**
     * @param array<FieldSorting> $sortings
     */
    public function setSortings(array $sortings): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            '\Cicada\Storefront\Framework\Page\StorefrontSearchResult will be removed use \Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult instead'
        );

        $this->sortings = $sortings;
    }

    public function getSorting(): ?string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            '\Cicada\Storefront\Framework\Page\StorefrontSearchResult will be removed use \Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult instead'
        );

        return $this->sorting;
    }

    public function setSorting(?string $sorting): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            '\Cicada\Storefront\Framework\Page\StorefrontSearchResult will be removed use \Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult instead'
        );

        $this->sorting = $sorting;
    }
}
