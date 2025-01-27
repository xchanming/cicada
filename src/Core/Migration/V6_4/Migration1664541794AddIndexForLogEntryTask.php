<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1664541794AddIndexForLogEntryTask extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1664541794;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE `log_entry` ADD INDEX `idx.log_entry.created_at` (`created_at`)');
        } catch (\Exception) {
            // index already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
