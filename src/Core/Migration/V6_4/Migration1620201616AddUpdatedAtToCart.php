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
class Migration1620201616AddUpdatedAtToCart extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1620201616;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `cart` ADD COLUMN `updated_at` DATETIME(3) NULL;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
