<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_6;

use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Migration\V6_6\Migration1735112885AddDefaultSearchResultSorting;
use Cicada\Tests\Migration\MigrationTestTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Migration1735112885AddDefaultSearchResultSorting::class)]
class Migration1735112885AddDefaultSearchResultSortingTest extends TestCase
{
    use MigrationTestTrait;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
        $this->connection->delete('system_config', ['configuration_key' => 'core.listing.defaultSearchResultSorting']);
    }

    public function testMigration(): void
    {
        static::assertEmpty($this->getConfig());

        $migration = new Migration1735112885AddDefaultSearchResultSorting();
        $migration->update($this->connection);

        $record = $this->getConfig();

        static::assertArrayHasKey('configuration_key', $record);
        static::assertArrayHasKey('configuration_value', $record);
        static::assertSame('core.listing.defaultSearchResultSorting', $record['configuration_key']);

        $value = \sprintf('{"_value": "%s"}', Uuid::randomHex());
        $this->connection->update('system_config', ['configuration_value' => $value], ['configuration_key' => 'core.listing.defaultSearchResultSorting']);

        $migration->update($this->connection);

        $record = $this->getConfig();

        static::assertArrayHasKey('configuration_key', $record);
        static::assertArrayHasKey('configuration_value', $record);
        static::assertSame('core.listing.defaultSearchResultSorting', $record['configuration_key']);
        static::assertSame($value, $record['configuration_value']);
    }

    /**
     * @return array<string, mixed>
     */
    private function getConfig(): array
    {
        return $this->connection->fetchAssociative(
            'SELECT * FROM system_config WHERE configuration_key = \'core.listing.defaultSearchResultSorting\''
        ) ?: [];
    }
}
