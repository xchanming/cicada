<?php declare(strict_types=1);

namespace Cicada\Core\Content\Sitemap\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('services-settings')]
class SitemapGenerateTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'cicada.sitemap_generate';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
