<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1669298267AddIconCacheDefaultValue extends MigrationStep
{
    final public const CONFIG_KEY = 'core.storefrontSettings.iconCache';

    public function getCreationTimestamp(): int
    {
        return 1669298267;
    }

    public function update(Connection $connection): void
    {
        if ($connection->fetchOne('SELECT 1 FROM `system_config` WHERE `configuration_key` = ? LIMIT 1', [self::CONFIG_KEY])) {
            return;
        }

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => self::CONFIG_KEY,
            'configuration_value' => json_encode(['_value' => true]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
}
