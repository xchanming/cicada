<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1713345551AddAppManagedColumn;

/**
 * @internal
 */
#[CoversClass(Migration1713345551AddAppManagedColumn::class)]
class Migration1713345551AddAppManagedColumnTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();

        try {
            $this->connection->executeStatement(
                'ALTER TABLE `app` DROP COLUMN `self_managed`;'
            );
        } catch (\Throwable) {
        }
    }

    public function testMigration(): void
    {
        static::assertFalse(EntityDefinitionQueryHelper::columnExists($this->connection, 'app', 'self_managed'));

        $migration = new Migration1713345551AddAppManagedColumn();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertTrue(EntityDefinitionQueryHelper::columnExists($this->connection, 'app', 'self_managed'));
    }
}
