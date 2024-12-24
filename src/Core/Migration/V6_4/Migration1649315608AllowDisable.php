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
class Migration1649315608AllowDisable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1649315608;
    }

    public function update(Connection $connection): void
    {
        $columns = array_column($connection->fetchAllAssociative('SHOW COLUMNS FROM `app`'), 'Field');

        if (\in_array('allow_disable', $columns, true)) {
            return;
        }

        $connection->executeStatement('ALTER TABLE `app` ADD `allow_disable` TINYINT(1) NOT NULL DEFAULT 1 AFTER `active`;');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
