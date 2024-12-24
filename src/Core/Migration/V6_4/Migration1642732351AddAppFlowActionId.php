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
#[Package('core')]
class Migration1642732351AddAppFlowActionId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1642732351;
    }

    public function update(Connection $connection): void
    {
        $appFlowActionIdColumn = $connection->fetchOne(
            'SHOW COLUMNS FROM `flow_sequence` WHERE `Field` LIKE :column;',
            ['column' => 'app_flow_action_id']
        );

        if ($appFlowActionIdColumn === false) {
            $connection->executeStatement('ALTER TABLE `flow_sequence` ADD COLUMN `app_flow_action_id` BINARY(16) DEFAULT null AFTER `flow_id`');
            $connection->executeStatement(
                'ALTER TABLE `flow_sequence`
                ADD CONSTRAINT `fk.flow_sequence.app_flow_action_id` FOREIGN KEY (`app_flow_action_id`) REFERENCES `app_flow_action` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
