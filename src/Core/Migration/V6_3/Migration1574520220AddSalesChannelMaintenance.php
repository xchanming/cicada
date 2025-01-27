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
class Migration1574520220AddSalesChannelMaintenance extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1574520220;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `sales_channel` ADD `maintenance` tinyint(1) NOT NULL DEFAULT 0 AFTER `active`
        ');
        $connection->executeStatement('
            ALTER TABLE `sales_channel` ADD `maintenance_ip_whitelist` JSON NULL AFTER `maintenance`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
