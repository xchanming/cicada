<?php declare(strict_types=1);

namespace Cicada\Core\Service\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @internal
 */
#[Package('core')]
class InstallServicesTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'services.install';
    }

    public static function getDefaultInterval(): int
    {
        return parent::DAILY;
    }
}
