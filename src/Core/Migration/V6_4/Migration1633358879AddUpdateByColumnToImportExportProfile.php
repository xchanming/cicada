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
class Migration1633358879AddUpdateByColumnToImportExportProfile extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1633358879;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `import_export_profile` ADD `update_by` json NULL AFTER `mapping`');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
