<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class Migration1673263104RemoveCartNameColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1673263104;
    }

    public function update(Connection $connection): void
    {
        $isCartNameNullable = <<<'SQL'
            SELECT is_nullable
            FROM information_schema.columns
            WHERE table_schema = ?
            AND table_name = 'cart'
            AND column_name = 'name';
        SQL;

        if ($connection->fetchOne($isCartNameNullable, [$connection->getDatabase()]) === 'NO') {
            $connection->executeStatement(
                'ALTER TABLE `cart` CHANGE `name` `name` VARCHAR(500) COLLATE utf8mb4_unicode_ci'
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'cart', 'name');
    }
}
