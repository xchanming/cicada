<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class Migration1694426018AddEntityIndexToStateMachineHistory extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1694426018;
    }

    public function update(Connection $connection): void
    {
        $indexes = $connection->executeQuery('
            SELECT INDEX_NAME FROM information_schema.STATISTICS
                WHERE table_schema = :database
                  AND table_name = \'state_machine_history\'
                  AND (COLUMN_NAME = \'referenced_id\'
                    OR COLUMN_NAME = \'referenced_version_id\');
        ', ['database' => $connection->getDatabase()])->fetchFirstColumn();

        if (!\in_array('idx.state_machine_history.referenced_entity', $indexes, true)) {
            $connection->executeStatement('
                CREATE INDEX `idx.state_machine_history.referenced_entity`
                    ON `state_machine_history` (`referenced_id`, `referenced_version_id`);
            ');
        }
    }
}
