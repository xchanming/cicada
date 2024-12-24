<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Sitemap;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('services-settings')]
class SitemapPage extends Struct
{
    /**
     * @var array
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $sitemaps;

    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    public function setSitemaps(array $sitemaps): void
    {
        $this->sitemaps = $sitemaps;
    }
}
