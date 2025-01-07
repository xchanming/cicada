<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Wishlist;

use Cicada\Core\Content\Product\SalesChannel\ProductListResponse;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Pagelet\Pagelet;

#[Package('discovery')]
class GuestWishlistPagelet extends Pagelet
{
    /**
     * @var ProductListResponse
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $searchResult;

    public function getSearchResult(): ProductListResponse
    {
        return $this->searchResult;
    }

    public function setSearchResult(ProductListResponse $searchResult): void
    {
        $this->searchResult = $searchResult;
    }
}
