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
class Migration1602494495SetUsersAsAdmins extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1602494495;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE `user` SET `admin` = 1, `title` = `Admin`');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
