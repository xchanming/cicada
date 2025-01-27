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
class Migration1572957455AddAffiliateTrackingColumns extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572957455;
    }

    public function update(Connection $connection): void
    {
        $this->addCustomerColumns($connection);

        $this->addOrderColumns($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addCustomerColumns(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `customer`
            ADD COLUMN `affiliate_code` varchar(255) NULL AFTER `custom_fields`,
            ADD COLUMN `campaign_code` varchar(255) NULL AFTER `affiliate_code`
        ');
    }

    private function addOrderColumns(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `order`
            ADD COLUMN `affiliate_code` varchar(255) NULL AFTER `custom_fields`,
            ADD COLUMN `campaign_code` varchar(255) NULL AFTER `affiliate_code`
        ');
    }
}
