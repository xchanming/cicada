<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Webhook\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @internal
 */
#[Package('core')]
class CleanupWebhookEventLogTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'webhook_event_log.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
