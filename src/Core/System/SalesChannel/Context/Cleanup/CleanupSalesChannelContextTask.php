<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Context\Cleanup;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('discovery')]
class CleanupSalesChannelContextTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'sales_channel_context.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
