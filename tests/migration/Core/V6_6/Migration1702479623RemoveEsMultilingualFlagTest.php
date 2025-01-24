<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Adapter\Storage\MySQLKeyValueStorage;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1702479623RemoveEsMultilingualFlag;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(Migration1702479623RemoveEsMultilingualFlag::class)]
class Migration1702479623RemoveEsMultilingualFlagTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testUpdateDestructiveRemovesColumn(): void
    {
        $storage = new MySQLKeyValueStorage($this->connection);
        $storage->set('enable-multilingual-index', true);

        static::assertTrue($storage->has('enable-multilingual-index'));

        $migration = new Migration1702479623RemoveEsMultilingualFlag();
        $migration->update($this->connection);
        $storage->reset();
        static::assertFalse($storage->has('enable-multilingual-index'));

        $migration->update($this->connection);
        $storage->reset();
        static::assertFalse($storage->has('enable-multilingual-index'));
    }
}
