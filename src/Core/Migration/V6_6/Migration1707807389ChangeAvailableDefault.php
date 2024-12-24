<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_6;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('core')]
class Migration1707807389ChangeAvailableDefault extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1707807389;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product` CHANGE `available` `available` tinyint(1) NOT NULL DEFAULT \'0\';');
    }
}
