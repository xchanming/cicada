<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_7;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('services-settings')]
class Migration1717572627RemoveImportExportProfileName extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1717572627;
    }

    public function update(Connection $connection): void
    {
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'import_export_profile', 'name');
    }
}
