<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1718615305AddEuToCountryTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1718615305;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            <<<SQL
            ALTER TABLE `country`
            ADD COLUMN `is_eu` BOOLEAN NOT NULL DEFAULT 0;
            SQL,
        );
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
