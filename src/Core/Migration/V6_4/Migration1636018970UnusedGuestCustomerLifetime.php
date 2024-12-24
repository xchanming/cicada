<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

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
class Migration1636018970UnusedGuestCustomerLifetime extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1636018970;
    }

    public function update(Connection $connection): void
    {
        $exists = $connection->fetchOne('
            SELECT configuration_key
            FROM `system_config`
            WHERE configuration_key = "core.loginRegistration.unusedGuestCustomerLifetime"
        ');

        if (!$exists) {
            $connection->insert('system_config', [
                'id' => Uuid::randomBytes(),
                'configuration_key' => 'core.loginRegistration.unusedGuestCustomerLifetime',
                'configuration_value' => json_encode(['_value' => 86400]),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
