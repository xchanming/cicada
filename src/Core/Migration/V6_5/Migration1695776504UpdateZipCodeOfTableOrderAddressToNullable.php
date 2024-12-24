<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('checkout')]
class Migration1695776504UpdateZipCodeOfTableOrderAddressToNullable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1695776504;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `order_address` MODIFY COLUMN `zipcode` varchar(50) NULL');
    }

    /**
     * @codeCoverageIgnore
     */
}
