<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Version\Cleanup;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('core')]
class CleanupVersionTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'version.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
