<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\ScheduledTask;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('services-settings')]
class CleanupImportExportFileTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'import_export_file.cleanup';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
