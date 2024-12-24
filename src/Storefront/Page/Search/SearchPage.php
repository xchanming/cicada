<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Search;

use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('services-settings')]
class SearchPage extends Page
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $searchTerm;

    /**
     * @var ProductListingResult
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $listing;

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    public function getListing(): ProductListingResult
    {
        return $this->listing;
    }

    public function setListing(ProductListingResult $listing): void
    {
        $this->listing = $listing;
    }
}
