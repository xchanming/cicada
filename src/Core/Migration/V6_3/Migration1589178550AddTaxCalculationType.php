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
class Migration1589178550AddTaxCalculationType extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589178550;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `sales_channel` ADD `tax_calculation_type` varchar(50) NOT NULL DEFAULT \'horizontal\' AFTER analytics_id');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
