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
class Migration1650249241UpdateTypeOfDepartmentAddress extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1650249241;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `customer_address`
                MODIFY COLUMN `department` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL;
        ');

        $connection->executeStatement('
            ALTER TABLE `order_address`
                MODIFY COLUMN `department` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
