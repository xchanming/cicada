<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1707807389ChangeAvailableDefault;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Migration1707807389ChangeAvailableDefault::class)]
class Migration1707807389ChangeAvailableDefaultTest extends TestCase
{
    public function testMigration(): void
    {
        $connection = KernelLifecycleManager::getConnection();

        $migration = new Migration1707807389ChangeAvailableDefault();
        $migration->update($connection);

        $available = $connection->fetchOne('SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_NAME = "product" AND COLUMN_NAME = "available"');

        static::assertEquals('0', $available);
    }
}
