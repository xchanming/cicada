<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cleanup;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('inventory')]
class CleanupUnusedDownloadMediaTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'product_download.media.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return 2628000; // 1 month
    }
}
