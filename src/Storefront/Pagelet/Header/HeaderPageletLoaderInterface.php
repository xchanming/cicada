<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Header;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
interface HeaderPageletLoaderInterface
{
    public function load(Request $request, SalesChannelContext $context): HeaderPagelet;
}
