<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

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
class Migration1603179023AddDefaultSettingConfigValueForProductListingPerPage extends MigrationStep
{
    final public const CONFIG_KEY = 'core.listing.productsPerPage';

    public function getCreationTimestamp(): int
    {
        return 1603179023;
    }

    public function update(Connection $connection): void
    {
        if ($this->configPresent($connection)) {
            return;
        }

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => self::CONFIG_KEY,
            'configuration_value' => json_encode(['_value' => 24]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function configPresent(Connection $connection): bool
    {
        return $connection->fetchOne(
            'SELECT `id` FROM `system_config` WHERE `configuration_key` = :config_key LIMIT 1;',
            ['config_key' => self::CONFIG_KEY]
        ) !== false;
    }
}
