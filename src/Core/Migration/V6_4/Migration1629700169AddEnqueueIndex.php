<?php declare(strict_types=1);

namespace Cicada\Core\Migration\V6_4;

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
class Migration1629700169AddEnqueueIndex extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1629700169;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE `enqueue` ADD INDEX `delivery_id` (`delivery_id`);');
        } catch (Exception) {
            // index already exists
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
