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
class Migration1671723392AddWebhookLifetimeConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1671723392;
    }

    public function update(Connection $connection): void
    {
        $config = $connection->fetchAssociative(
            'SELECT * FROM system_config WHERE configuration_key = \'core.webhook.entryLifetimeSeconds\''
        );

        if ($config !== false) {
            return;
        }

        $connection->insert('system_config', [
            'id' => Uuid::randomBytes(),
            'configuration_key' => 'core.webhook.entryLifetimeSeconds',
            'configuration_value' => '{"_value": "1209600"}', // 2 weeks
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
    }
}
