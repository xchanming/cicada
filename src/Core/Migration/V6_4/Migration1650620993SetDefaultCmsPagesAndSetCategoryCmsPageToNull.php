<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1650620993SetDefaultCmsPagesAndSetCategoryCmsPageToNull extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1650620993;
    }

    public function update(Connection $connection): void
    {
        // set system config key for categories
        $cmsPageId = $this->getDefaultCmsPageIdFromType('product_list', $connection);
        $this->setSystemConfig(CategoryDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_CATEGORY, $cmsPageId, $connection);

        // set system config key for products
        $this->setSystemConfig(ProductDefinition::CONFIG_KEY_DEFAULT_CMS_PAGE_PRODUCT, Defaults::CMS_PRODUCT_DETAIL_PAGE, $connection);

        $connection->executeStatement('UPDATE category SET cms_page_id = null WHERE cms_page_id = :defaultCmsPageId;', ['defaultCmsPageId' => Uuid::fromHexToBytes($cmsPageId)]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function setSystemConfig(string $key, string $value, Connection $connection): void
    {
        $id = $connection->fetchOne('
            SELECT id
            FROM system_config
            WHERE configuration_key = :configurationKey;
        ', ['configurationKey' => $key]);

        if ($id) {
            // id is already set
            return;
        }

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => $key,
            'configuration_value' => json_encode(['_value' => $value], \JSON_THROW_ON_ERROR),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    private function getDefaultCmsPageIdFromType(string $cmsPageType, Connection $connection): string
    {
        $cmsPageId = $connection->fetchOne('
            SELECT id
            FROM  cms_page
            WHERE type = :cmsPageType
            ORDER BY locked DESC, created_at ASC;
       ', ['cmsPageType' => $cmsPageType]);

        return Uuid::fromBytesToHex($cmsPageId);
    }
}
