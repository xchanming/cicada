<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Package('core')]
class UpdateAppsTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'app_update';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return true;
    }

    public static function shouldRun(ParameterBagInterface $bag): bool
    {
        return $bag->get('cicada.deployment.runtime_extension_management');
    }
}
