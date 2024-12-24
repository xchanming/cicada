<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Suggest;

use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('services-settings')]
class SuggestPage extends Page
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $searchTerm;

    /**
     * @var EntitySearchResult<ProductCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $searchResult;

    /**
     * @return EntitySearchResult<ProductCollection>
     */
    public function getSearchResult(): EntitySearchResult
    {
        return $this->searchResult;
    }

    /**
     * @param EntitySearchResult<ProductCollection> $searchResult
     */
    public function setSearchResult(EntitySearchResult $searchResult): void
    {
        $this->searchResult = $searchResult;
    }

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }
}
