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
class Migration1614240671AddVatIdsToOrderCustomer extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1614240671;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `order_customer`
            ADD COLUMN `vat_ids` JSON NULL DEFAULT NULL AFTER `title`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
