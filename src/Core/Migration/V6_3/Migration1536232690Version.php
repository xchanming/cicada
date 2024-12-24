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
class Migration1536232690Version extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536232690;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `version` (
              `id`          BINARY(16)                              NOT NULL,
              `name`        VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
              `created_at`  DATETIME(3)                             NOT NULL,
              `updated_at`  DATETIME(3)                             NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
