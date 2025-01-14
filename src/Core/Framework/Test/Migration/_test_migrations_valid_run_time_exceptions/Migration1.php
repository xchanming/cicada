<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Migration\_test_migrations_valid_run_time_exceptions;

use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Migration1 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 20;
    }

    public function update(Connection $connection): void
    {
        // nth
    }

    public function updateDestructive(Connection $connection): void
    {
        throw new \RuntimeException('update destructive');
    }
}
