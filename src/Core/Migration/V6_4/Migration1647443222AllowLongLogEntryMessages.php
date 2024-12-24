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
class Migration1647443222AllowLongLogEntryMessages extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1647443222;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `log_entry`
                MODIFY COLUMN `message` LONGTEXT NOT NULL;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
