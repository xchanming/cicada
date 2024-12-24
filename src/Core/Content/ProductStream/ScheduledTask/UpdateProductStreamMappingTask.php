<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductStream\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('inventory')]
class UpdateProductStreamMappingTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'product_stream.mapping.update';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
