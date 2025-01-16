<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Navigation;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
interface NavigationPageLoaderInterface
{
    public function load(Request $request, SalesChannelContext $context): NavigationPage;
}
