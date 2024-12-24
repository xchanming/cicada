<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1673964565MigrateToReferencedColumns extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1673964565;
    }

    public function update(Connection $connection): void
    {
        $columns = $connection->fetchAllAssociativeIndexed(
            'SELECT COLUMN_NAME,EXTRA FROM information_schema.columns
                WHERE table_schema = :database
                  AND table_name = \'state_machine_history\'
                  AND (COLUMN_NAME = \'referenced_id\'
                    OR COLUMN_NAME = \'referenced_version_id\'
                    OR COLUMN_NAME = \'entity_id\');',
            ['database' => $connection->getDatabase()]
        );

        if ($columns['referenced_id']['EXTRA'] === 'STORED GENERATED') {
            $connection->executeStatement(
                'ALTER TABLE `state_machine_history`
                 MODIFY COLUMN `referenced_id` BINARY(16) NOT NULL;'
            );
        }

        if ($columns['referenced_version_id']['EXTRA'] === 'STORED GENERATED') {
            $connection->executeStatement(
                'ALTER TABLE `state_machine_history`
                 MODIFY COLUMN `referenced_version_id` BINARY(16) NOT NULL;'
            );
        }

        /** @phpstan-ignore cicada.dropStatement (Too late and too complex to revert and fix) */
        $this->dropColumnIfExists($connection, 'state_machine_history', 'entity_id');
    }
}
