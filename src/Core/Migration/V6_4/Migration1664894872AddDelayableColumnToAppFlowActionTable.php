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
class Migration1664894872AddDelayableColumnToAppFlowActionTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1664894872;
    }

    public function update(Connection $connection): void
    {
        $field = $connection->fetchOne(
            'SHOW COLUMNS FROM `app_flow_action` WHERE `Field` LIKE :column;',
            ['column' => 'delayable']
        );

        if (!empty($field)) {
            return;
        }

        $connection->executeStatement('
            ALTER TABLE `app_flow_action` ADD COLUMN `delayable` BOOL NOT NULL DEFAULT FALSE AFTER `url`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
