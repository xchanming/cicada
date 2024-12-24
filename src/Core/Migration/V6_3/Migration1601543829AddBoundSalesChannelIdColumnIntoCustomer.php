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
#[Package('core')]
class Migration1601543829AddBoundSalesChannelIdColumnIntoCustomer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1601543829;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `customer`
            ADD COLUMN `bound_sales_channel_id` BINARY(16) NULL DEFAULT NULL,
            ADD CONSTRAINT `fk.customer.bound_sales_channel_id` FOREIGN KEY (`bound_sales_channel_id`) REFERENCES `sales_channel` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
