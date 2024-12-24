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
class Migration1602062376AddUniqueConstraintForEmailAndBoundSalesChannelIdIntoCustomerTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1602062376;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `customer` ADD UNIQUE `uniq.customer.email_bound_sales_channel_id`(`email`, `bound_sales_channel_id`);');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
