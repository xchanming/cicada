<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Cleanup;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('checkout')]
class CleanupCartTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'cart.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
