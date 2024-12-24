<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\ImportExport\ImportExportProfileTranslationDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\Traits\ImportTranslationsTrait;
use Cicada\Core\Migration\Traits\Translations;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1625831469AddImportExportCrossSellingProfile extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1625831469;
    }

    public function update(Connection $connection): void
    {
        $id = Uuid::randomBytes();

        $connection->insert('import_export_profile', [
            'id' => $id,
            'name' => 'Default cross-selling',
            'system_default' => 1,
            'source_entity' => 'product_cross_selling',
            'file_type' => 'text/csv',
            'delimiter' => ';',
            'enclosure' => '"',
            'mapping' => json_encode([
                ['key' => 'id', 'mappedKey' => 'id'],
                ['key' => 'translations.DEFAULT.name', 'mappedKey' => 'name'],
                ['key' => 'productId', 'mappedKey' => 'product_id'],
                ['key' => 'active', 'mappedKey' => 'active'],
                ['key' => 'position', 'mappedKey' => 'position'],
                ['key' => 'limit', 'mappedKey' => 'limit'],
                ['key' => 'type', 'mappedKey' => 'type'],
                ['key' => 'sortBy', 'mappedKey' => 'sort_by'],
                ['key' => 'sortDirection', 'mappedKey' => 'sort_direction'],
                ['key' => 'assignedProducts', 'mappedKey' => 'assigned_products'],
            ]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        $translations = new Translations(
            [
                'import_export_profile_id' => $id,
                'label' => 'Standardprofil Cross-Selling',
            ],
            [
                'import_export_profile_id' => $id,
                'label' => 'Default cross-selling',
            ]
        );

        $this->importTranslation(ImportExportProfileTranslationDefinition::ENTITY_NAME, $translations, $connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
