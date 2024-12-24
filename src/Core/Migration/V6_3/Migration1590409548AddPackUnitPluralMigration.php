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
class Migration1590409548AddPackUnitPluralMigration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1590409548;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `product_translation`
            ADD COLUMN `pack_unit_plural` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
