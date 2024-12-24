<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\MessageQueue\fixtures;

use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @internal
 */
class TestRescheduleOnFailureTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return self::class;
    }

    public static function getDefaultInterval(): int
    {
        return 1;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }
}
