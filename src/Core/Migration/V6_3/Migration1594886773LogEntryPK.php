<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_3;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('framework')]
class Migration1594886773LogEntryPK extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1594886773;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('
                ALTER TABLE `log_entry`
                ADD PRIMARY KEY (`id`);
            ');
        } catch (Exception) {
            // PK already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // nothing
    }
}
