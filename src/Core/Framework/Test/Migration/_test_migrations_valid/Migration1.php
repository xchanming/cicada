<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Migration\_test_migrations_valid;

use Doctrine\DBAL\Connection;
use Cicada\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1;
    }

    public function update(Connection $connection): void
    {
        // nth
    }

    public function updateDestructive(Connection $connection): void
    {
        // nth
    }
}
