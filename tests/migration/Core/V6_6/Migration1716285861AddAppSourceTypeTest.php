<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Migration\V6_6\Migration1716285861AddAppSourceType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Migration1716285861AddAppSourceType::class)]
class Migration1716285861AddAppSourceTypeTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();

        try {
            $this->connection->executeStatement(
                'ALTER TABLE `app` DROP COLUMN `source_type`;'
            );
        } catch (\Throwable) {
        }
    }

    public function testMigration(): void
    {
        static::assertFalse($this->columnExists());

        $migration = new Migration1716285861AddAppSourceType();
        $migration->update($this->connection);
        $migration->update($this->connection);

        static::assertTrue($this->columnExists());
    }

    private function columnExists(): bool
    {
        $field = $this->connection->fetchOne(
            'SHOW COLUMNS FROM `app` WHERE `Field` = "source_type";',
        );

        return $field === 'source_type';
    }
}
