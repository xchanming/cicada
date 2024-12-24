<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Defaults;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1610904608TemporarilyDisableWishlistAsDefault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1610904608;
    }

    public function update(Connection $connection): void
    {
        $configId = $connection->fetchOne('SELECT id FROM system_config WHERE configuration_key = :key', [
            'key' => 'core.cart.wishlistEnabled',
        ]);

        if (!$configId) {
            return;
        }

        $connection->update('system_config', [
            'configuration_value' => json_encode(['_value' => false]),
            'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => $configId,
        ]);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
