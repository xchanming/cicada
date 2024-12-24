<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1578485775UseStableUpdateChannel extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1578485775;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(
            'UPDATE system_config
             SET configuration_value = :value
             WHERE configuration_key = :key',
            [
                'key' => 'core.update.channel',
                'value' => json_encode(['_value' => 'stable']),
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
