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
class Migration1565007156RemoveAutoIncrement extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1565007156;
    }

    public function update(Connection $connection): void
    {
        // implement update
    }

    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE product DROP COLUMN auto_increment');
        $connection->executeStatement('ALTER TABLE category DROP COLUMN auto_increment');
    }
}
