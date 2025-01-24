<?php declare(strict_types=1);

namespace Cicada\Administration\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('framework')]
class Migration1726132532ExpandNotificationMessage extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1726132532;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            ALTER TABLE `notification` MODIFY `message` LONGTEXT;
        ');
    }
}
