<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\SalesChannel;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('discovery')]
abstract class AbstractSitemapRoute
{
    abstract public function load(Request $request, SalesChannelContext $context): SitemapRouteResponse;

    abstract public function getDecorated(): AbstractSitemapRoute;
}
