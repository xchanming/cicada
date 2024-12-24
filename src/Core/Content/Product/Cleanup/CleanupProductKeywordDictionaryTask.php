<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Cleanup;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('services-settings')]
class CleanupProductKeywordDictionaryTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'product_keyword_dictionary.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::WEEKLY;
    }
}
