<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_5;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1708685281ChangeAppPathColumnToLongerVarchar extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1708685281;
    }

    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `app` MODIFY COLUMN `path` VARCHAR(4096);';

        $connection->executeStatement($sql);
    }
}
