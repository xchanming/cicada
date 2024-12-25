<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Content\Product\SalesChannel\Sorting\ProductSortingDefinition;
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
class Migration1600338271AddTopsellerSorting extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1600338271;
    }

    public function update(Connection $connection): void
    {
        $this->createDefaultSortingsWithTranslations($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    public function createDefaultSortingsWithTranslations(Connection $connection): void
    {
        $sorting = $this->getTopsellerSorting();

        $translations = $sorting['translations'];

        unset($sorting['translations']);

        $connection->insert(ProductSortingDefinition::ENTITY_NAME, $sorting);

        $translations = new Translations(
            ['product_sorting_id' => $sorting['id'], 'label' => $translations['zh-CN']],
            ['product_sorting_id' => $sorting['id'], 'label' => $translations['en-GB']]
        );

        $this->importTranslation('product_sorting_translation', $translations, $connection);
    }

    /**
     * @return array{id: string, priority: int, active: int, locked: int, fields: string, created_at: string, translations: array{zh-CN: string, en-GB: string}}
     */
    private function getTopsellerSorting(): array
    {
        return [
            'id' => Uuid::randomBytes(),
            'url_key' => 'topseller',
            'priority' => 0,
            'active' => 1,
            'locked' => 0,
            'fields' => json_encode([['field' => 'product.sales', 'order' => 'desc', 'priority' => 1, 'naturalSorting' => 0]], \JSON_THROW_ON_ERROR),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'translations' => [
                'zh-CN' => 'Topseller',
                'en-GB' => 'Topseller',
            ],
        ];
    }
}
