<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(MigrationCollection::class)]
class MigrationNameAndTimestampTest extends TestCase
{
    use KernelTestBehaviour;

    public function testMigrationNameAndTimestampAreNamedAfterOptionalConvention(): void
    {
        $loader = static::getContainer()->get(MigrationCollectionLoader::class);
        $migrationCollection = $loader->collectAll();

        foreach ($migrationCollection as $migrations) {
            foreach ($migrations->getMigrationSteps() as $className => $migration) {
                $matches = [];
                $result = preg_match('/\\\\(?<name>Migration(?<timestamp>\d+)\w+)$/', (string) $className, $matches);

                static::assertSame(1, $result, \sprintf(
                    'Invalid migration name "%s". Example for a valid format: Migration1536232684Order',
                    $className
                ));

                $timestamp = (int) ($matches['timestamp'] ?? 0);
                static::assertSame($migration->getCreationTimestamp(), $timestamp, \sprintf(
                    'Timestamp in migration name "%s" does not match timestamp of method "getCreationTimestamp"',
                    $className
                ));
            }
        }
    }
}
