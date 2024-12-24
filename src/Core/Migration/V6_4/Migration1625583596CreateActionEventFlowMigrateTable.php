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
class Migration1625583596CreateActionEventFlowMigrateTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1625583596;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sales_channel_rule` (
                `rule_id`           BINARY(16)    NOT NULL,
                `sales_channel_id`  BINARY(16)    NOT NULL,
                PRIMARY KEY (`rule_id`,`sales_channel_id`),
                CONSTRAINT `fk.sales_channel_rule.rule_id` FOREIGN KEY (`rule_id`) REFERENCES `rule` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.sales_channel_rule.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
