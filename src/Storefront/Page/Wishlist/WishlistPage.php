<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Wishlist;

use Cicada\Core\Checkout\Customer\SalesChannel\LoadWishlistRouteResponse;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('storefront')]
class WishlistPage extends Page
{
    /**
     * @var LoadWishlistRouteResponse
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $wishlist;

    public function getWishlist(): LoadWishlistRouteResponse
    {
        return $this->wishlist;
    }

    public function setWishlist(LoadWishlistRouteResponse $wishlist): void
    {
        $this->wishlist = $wishlist;
    }
}
