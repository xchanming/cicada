<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Footer;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
interface FooterPageletLoaderInterface
{
    public function load(Request $request, SalesChannelContext $salesChannelContext): FooterPagelet;
}
