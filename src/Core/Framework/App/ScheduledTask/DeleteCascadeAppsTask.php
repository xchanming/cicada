<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('core')]
class DeleteCascadeAppsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'app_delete';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
