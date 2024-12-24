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
class Migration1604568928AddConfigurableToApp extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1604568928;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `app`
            ADD `configurable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
