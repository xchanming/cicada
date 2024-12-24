<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Wishlist;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Pagelet\PageletLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
class GuestWishlistPageletLoadedEvent extends PageletLoadedEvent
{
    /**
     * @var GuestWishlistPagelet
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $pagelet;

    public function __construct(
        GuestWishlistPagelet $pagelet,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->pagelet = $pagelet;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPagelet(): GuestWishlistPagelet
    {
        return $this->pagelet;
    }
}
