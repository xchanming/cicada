<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Menu\Offcanvas;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
interface MenuOffcanvasPageletLoaderInterface
{
    public function load(Request $request, SalesChannelContext $context): MenuOffcanvasPagelet;
}
