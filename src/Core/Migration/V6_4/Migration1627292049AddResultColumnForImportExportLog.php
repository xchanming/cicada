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
class Migration1627292049AddResultColumnForImportExportLog extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1627292049;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `import_export_log` ADD COLUMN `result` JSON NULL AFTER `config`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
