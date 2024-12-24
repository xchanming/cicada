<?php declare(strict_types=1);

namespace Cicada\Core\Content\ProductExport\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('inventory')]
class ProductExportGenerateTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'product_export_generate_task';
    }

    public static function getDefaultInterval(): int
    {
        return 60;
    }
}
