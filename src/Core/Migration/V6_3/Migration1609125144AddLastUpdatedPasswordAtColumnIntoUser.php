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
class Migration1609125144AddLastUpdatedPasswordAtColumnIntoUser extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1609125144;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `user` ADD COLUMN `last_updated_password_at` DATETIME(3) NULL AFTER `store_token`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
