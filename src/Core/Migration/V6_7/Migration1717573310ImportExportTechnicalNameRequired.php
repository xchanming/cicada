<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_7;

use Cicada\Core\Content\ImportExport\ImportExportProfileDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('services-settings')]
class Migration1717573310ImportExportTechnicalNameRequired extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1717573310;
    }

    public function update(Connection $connection): void
    {
        $manager = $connection->createSchemaManager();
        $columns = $manager->listTableColumns(ImportExportProfileDefinition::ENTITY_NAME);

        if (\array_key_exists('technical_name', $columns) && !$columns['technical_name']->getNotnull()) {
            $connection->executeStatement('ALTER TABLE `import_export_profile` MODIFY COLUMN `technical_name` VARCHAR(255) NOT NULL');
        }
    }
}
