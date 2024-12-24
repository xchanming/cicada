<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
class Migration1622782058AddDeleteAtIntoIntegrationAndAclRole extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1622782058;
    }

    public function update(Connection $connection): void
    {
        $deletedAtColumnIntegration = $connection->fetchOne(
            'SHOW COLUMNS FROM `integration` WHERE `Field` LIKE :column;',
            ['column' => 'deleted_at']
        );

        if ($deletedAtColumnIntegration === false) {
            $connection->executeStatement('ALTER TABLE `integration` ADD COLUMN `deleted_at` DATETIME(3) NULL');
        }

        $deletedAtColumnAclRole = $connection->fetchOne(
            'SHOW COLUMNS FROM `acl_role` WHERE `Field` LIKE :column;',
            ['column' => 'deleted_at']
        );

        if ($deletedAtColumnAclRole === false) {
            $connection->executeStatement('ALTER TABLE `acl_role` ADD COLUMN `deleted_at` DATETIME(3) NULL');
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
