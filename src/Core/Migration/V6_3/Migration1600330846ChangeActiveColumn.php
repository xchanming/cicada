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
class Migration1600330846ChangeActiveColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600330846;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `product` CHANGE `active` `active` tinyint unsigned NULL AFTER `product_number`;');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
