<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use Cicada\Core\Content\Sitemap\Struct\Sitemap;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('services-settings')]
interface SitemapListerInterface
{
    /**
     * @return Sitemap[]
     */
    public function getSitemaps(SalesChannelContext $salesChannelContext): array;
}
