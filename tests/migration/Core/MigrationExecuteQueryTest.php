<?php declare(strict_types=1);

namespace Cicada\Tests\Migration\Core;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Cicada\Core\Framework\Migration\MigrationStep;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Migration\Test\NullConnection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(MigrationCollection::class)]
class MigrationExecuteQueryTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testExecuteQueryDoesNotPerformWriteOperations(): void
    {
        $nullConnection = new NullConnection();
        $nullConnection->setOriginalConnection(static::getContainer()->get(Connection::class));

        $loader = static::getContainer()->get(MigrationCollectionLoader::class);
        $migrationCollection = $loader->collectAll();

        $exceptions = [];
        try {
            foreach ($migrationCollection as $migrations) {
                /** @var class-string<MigrationStep> $migrationClass */
                foreach ($migrations->getMigrationSteps() as $migrationClass) {
                    $migration = new $migrationClass();
                    $migration->update($nullConnection);
                    $migration->updateDestructive($nullConnection);
                }
            }
        } catch (\Exception $e) {
            if ($e->getMessage() === NullConnection::EXCEPTION_MESSAGE) {
                $exceptions[] = \sprintf('%s Trace: %s', NullConnection::EXCEPTION_MESSAGE, $e->getTraceAsString());
            }
            // ignore error because it is possible that older migrations just don't work on read anymore
        }
        static::assertEmpty($exceptions, print_r($exceptions, true));
    }
}
