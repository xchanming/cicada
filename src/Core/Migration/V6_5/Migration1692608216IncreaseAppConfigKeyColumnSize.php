<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1692608216IncreaseAppConfigKeyColumnSize extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1692608216;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `app_config`
            MODIFY COLUMN `key` VARCHAR(255);
        ');
    }
}
