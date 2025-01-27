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
class Migration1655730949AddIsRunningColumnToProductExport extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1655730949;
    }

    public function update(Connection $connection): void
    {
        if ($this->columnExists($connection, 'product_export', 'is_running')) {
            return;
        }

        $sql = <<<'SQL'
            ALTER TABLE `product_export`
            ADD COLUMN `is_running` TINYINT(1) NOT NULL DEFAULT 0
        SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Implement updateDestructive() method.
    }
}
