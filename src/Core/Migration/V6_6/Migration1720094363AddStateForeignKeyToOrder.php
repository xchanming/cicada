<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1720094363AddStateForeignKeyToOrder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1720094363;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            UPDATE `order`
            SET `state_id` = (SELECT `initial_state_id` FROM `state_machine` WHERE `technical_name` = 'order.state')
            WHERE `state_id` NOT IN (SELECT `id` FROM `state_machine_state` WHERE `state_machine_id` = (SELECT `id` FROM `state_machine` WHERE `technical_name` = 'order.state'));
        SQL);

        $manager = $connection->createSchemaManager();
        $columns = $manager->listTableForeignKeys('order');

        if (\array_filter($columns, static fn (ForeignKeyConstraint $column) => $column->getForeignTableName() === 'state_machine_state' && $column->getLocalColumns() === ['state_id'] && $column->getForeignColumns() === ['id'])) {
            return;
        }

        $connection->executeStatement(<<<SQL
            ALTER TABLE `order`
            ADD CONSTRAINT `fk.order.state_id` FOREIGN KEY (`state_id`)
            REFERENCES `state_machine_state` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
        SQL);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
