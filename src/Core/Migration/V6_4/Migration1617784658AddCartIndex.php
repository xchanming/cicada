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
class Migration1617784658AddCartIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617784658;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `cart` ADD INDEX `idx.cart.created_at` (`created_at`)');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
