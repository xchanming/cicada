<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1599806595AddEventActionSalesChannel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1599806595;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `event_action_sales_channel` (
              `event_action_id` binary(16) NOT NULL,
              `sales_channel_id` binary(16) NOT NULL,
              PRIMARY KEY (`event_action_id`,`sales_channel_id`),
              KEY `sales_channel_id` (`sales_channel_id`),
              CONSTRAINT `fk.event_action_sales_channel.event_action_id` FOREIGN KEY (`event_action_id`) REFERENCES `event_action` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.event_action_sales_channel.sales_channel_id` FOREIGN KEY (`sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
