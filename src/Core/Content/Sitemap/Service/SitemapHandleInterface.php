<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
interface SitemapHandleInterface
{
    public function write(array $urls): void;

    public function finish(): void;
}
