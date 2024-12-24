<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\Service;

use Cicada\Core\Content\Sitemap\Exception\AlreadyLockedException;
use Cicada\Core\Content\Sitemap\Struct\SitemapGenerationResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('services-settings')]
interface SitemapExporterInterface
{
    public const SITEMAP_URL_LIMIT = 49999;

    public const STRATEGY_MANUAL = 1;
    public const STRATEGY_SCHEDULED_TASK = 2;
    public const STRATEGY_LIVE = 3;

    /**
     * @throws AlreadyLockedException
     */
    public function generate(SalesChannelContext $context, bool $force = false, ?string $lastProvider = null, ?int $offset = null): SitemapGenerationResult;
}
