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
class Migration1647260673AddIndexForEmail extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1647260673;
    }

    public function update(Connection $connection): void
    {
        $keys = \array_column($connection->fetchAllAssociative('SHOW INDEX FROM customer'), 'Key_name');

        if (\in_array('idx.email', $keys, true)) {
            return;
        }

        $connection->executeStatement('CREATE INDEX `idx.email` ON `customer` (`email`)');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
