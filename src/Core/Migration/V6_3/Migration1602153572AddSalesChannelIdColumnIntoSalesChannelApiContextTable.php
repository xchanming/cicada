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
class Migration1602153572AddSalesChannelIdColumnIntoSalesChannelApiContextTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1602153572;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `sales_channel_api_context` DROP FOREIGN KEY `fk.sales_channel_api_context.customer_id`;');

        $connection->executeStatement('ALTER TABLE `sales_channel_api_context` DROP INDEX `customer_id`;');

        $connection->executeStatement('ALTER TABLE `sales_channel_api_context` ADD `sales_channel_id` BINARY(16) NULL DEFAULT NULL AFTER `payload`;');

        $connection->executeStatement('
            ALTER TABLE `sales_channel_api_context`
            ADD CONSTRAINT `fk.sales_channel_api_context.sales_channel_id`
            FOREIGN KEY (`sales_channel_id`)
            REFERENCES `sales_channel` (`id`) ON DELETE CASCADE,
            ADD CONSTRAINT `fk.sales_channel_api_context.customer_id`
            FOREIGN KEY (`customer_id`)
            REFERENCES `customer` (`id`) ON DELETE CASCADE;
        ');

        $connection->executeStatement('ALTER TABLE `sales_channel_api_context` ADD UNIQUE `uniq.sales_channel_api_context.sales_channel_id_customer_id`(`sales_channel_id`, `customer_id`);');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
