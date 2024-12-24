<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Struct;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Collection;

/**
 * @extends Collection<Sitemap>
 */
#[Package('services-settings')]
class SitemapCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return Sitemap::class;
    }
}
