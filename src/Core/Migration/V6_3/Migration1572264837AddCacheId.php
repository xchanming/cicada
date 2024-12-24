<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1572264837AddCacheId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572264837;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('DELETE FROM app_config');

        try {
            $connection->executeStatement('ALTER TABLE app_config ADD PRIMARY KEY (`key`)');
        } catch (Exception) {
            // PK already exists
        }

        $connection->executeStatement(
            '
            INSERT IGNORE INTO app_config (`key`, `value`)
            VALUES (?, ?)',
            ['cache-id', Uuid::randomHex()]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
