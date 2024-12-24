<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Country;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Pagelet\PageletLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
class CountryStateDataPageletLoadedEvent extends PageletLoadedEvent
{
    public function __construct(
        protected CountryStateDataPagelet $pagelet,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        parent::__construct($salesChannelContext, $request);
    }

    public function getPagelet(): CountryStateDataPagelet
    {
        return $this->pagelet;
    }
}
