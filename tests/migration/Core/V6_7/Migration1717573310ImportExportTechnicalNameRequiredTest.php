<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core\V6_7;

use Cicada\Core\Content\ImportExport\ImportExportProfileDefinition;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Migration\V6_7\Migration1717573310ImportExportTechnicalNameRequired;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(Migration1717573310ImportExportTechnicalNameRequired::class)]
class Migration1717573310ImportExportTechnicalNameRequiredTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);

        $this->connection = static::getContainer()->get(Connection::class);
    }

    public function testMigrate(): void
    {
        $this->connection->executeStatement('ALTER TABLE `import_export_profile` MODIFY COLUMN `technical_name` VARCHAR(255) NULL');

        $migration = new Migration1717573310ImportExportTechnicalNameRequired();
        $migration->update($this->connection);
        $migration->update($this->connection);

        $manager = $this->connection->createSchemaManager();
        $columns = $manager->listTableColumns(ImportExportProfileDefinition::ENTITY_NAME);

        static::assertArrayHasKey('technical_name', $columns);
        static::assertTrue($columns['technical_name']->getNotnull());
    }
}
