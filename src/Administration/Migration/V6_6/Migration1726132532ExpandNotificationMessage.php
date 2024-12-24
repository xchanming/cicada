<?php declare(strict_types=1);

namespace Cicada\Administration\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
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
