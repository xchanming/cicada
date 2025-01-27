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
class Migration1627541488AddForeignKeyForSalesChannelIdIntoSystemConfigTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1627541488;
    }

    public function update(Connection $connection): void
    {
        $this->deleteConfigOfNonexistentSalesChannel($connection);
        $this->addSalesChannelIdForeignKey($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function deleteConfigOfNonexistentSalesChannel(Connection $connection): void
    {
        $connection->executeStatement(
            'DELETE FROM `system_config`
            WHERE `sales_channel_id` IS NOT NULL
            AND `sales_channel_id` NOT IN (SELECT `id` FROM `sales_channel`)'
        );
    }

    private function addSalesChannelIdForeignKey(Connection $connection): void
    {
        $connection->executeStatement(
            'ALTER TABLE `system_config`
            ADD CONSTRAINT `fk.system_config.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }
}
