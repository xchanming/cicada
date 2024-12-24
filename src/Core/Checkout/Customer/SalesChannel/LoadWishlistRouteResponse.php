<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistEntity;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\System\SalesChannel\StoreApiResponse;

#[Package('checkout')]
class LoadWishlistRouteResponse extends StoreApiResponse
{
    /**
     * @var CustomerWishlistEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $wishlist;

    /**
     * @var EntitySearchResult<ProductCollection>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productListing;

    /**
     * @param EntitySearchResult<ProductCollection> $listing
     */
    public function __construct(
        CustomerWishlistEntity $wishlist,
        EntitySearchResult $listing
    ) {
        $this->wishlist = $wishlist;
        $this->productListing = $listing;
        parent::__construct(new ArrayStruct([
            'wishlist' => $wishlist,
            'products' => $listing,
        ], 'wishlist_products'));
    }

    public function getWishlist(): CustomerWishlistEntity
    {
        return $this->wishlist;
    }

    public function setWishlist(CustomerWishlistEntity $wishlist): void
    {
        $this->wishlist = $wishlist;
    }

    /**
     * @return EntitySearchResult<ProductCollection>
     */
    public function getProductListing(): EntitySearchResult
    {
        return $this->productListing;
    }

    /**
     * @param EntitySearchResult<ProductCollection> $productListing
     */
    public function setProductListing(EntitySearchResult $productListing): void
    {
        $this->productListing = $productListing;
    }
}
