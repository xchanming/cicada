<?php

declare(strict_types=1);

namespace Cicada\Core\Migration\V6_10;

use Cicada\Core\Framework\Migration\MigrationStep;
use Doctrine\DBAL\Connection;

class Migration1720610756Bar extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1720610756;
    }

    /**
     * Just a PHPDoc to have a different line than in @see Migration1720610754FooBar
     */
    public function update(Connection $connection): void
    {
        // Make sure, that the namespace check is working correctly
        $this->dropColumnIfExists($connection, 'test_table', 'column');
    }

    public function updateDestructive(Connection $connection): void
    {
        // make sure, that only method calls from the same class are considered
        $this->doFoo($connection);
    }

    private function doFoo(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'test_table', 'column');
        $connection->executeStatement('ALTER TABLE `test_table` DROP COLUMN `column`;');

        $this->doBar($connection);
    }

    private function doBar(Connection $connection): void
    {
        $this->dropColumnIfExists($connection, 'test_table', 'column');
    }
}
