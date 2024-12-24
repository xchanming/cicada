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
class Migration1536233280CustomFieldSet extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1536233280;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE `custom_field_set` (
              `id` BINARY(16) NOT NULL PRIMARY KEY,
              `name` VARCHAR(255) NOT NULL,
              `config` JSON NULL,
              `active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3),
              CONSTRAINT `json.custom_field_set.config` CHECK(JSON_VALID(`config`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
