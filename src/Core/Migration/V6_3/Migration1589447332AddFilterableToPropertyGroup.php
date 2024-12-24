<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1589447332AddFilterableToPropertyGroup extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1589447332;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `property_group`
            ADD COLUMN `filterable` TINYINT(1) NOT NULL DEFAULT 1
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
