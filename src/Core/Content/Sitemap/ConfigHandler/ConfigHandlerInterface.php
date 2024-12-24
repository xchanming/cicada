<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\ConfigHandler;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
interface ConfigHandlerInterface
{
    public function getSitemapConfig(): array;
}
