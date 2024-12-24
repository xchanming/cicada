<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\ProductExport\ScheduledTask\ProductExportGenerateTask;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1633347511ChangeProductExportInterval extends MigrationStep
{
    private const OLD_INTERVAL = 86400;

    public function getCreationTimestamp(): int
    {
        return 1633347511;
    }

    public function update(Connection $connection): void
    {
        $connection->update(
            'scheduled_task',
            [
                'run_interval' => ProductExportGenerateTask::getDefaultInterval(),
            ],
            [
                'run_interval' => self::OLD_INTERVAL,
                'name' => ProductExportGenerateTask::getTaskName(),
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // nth
    }
}
