<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Maintenance\System\Service;

use Cicada\Core\Maintenance\System\Service\DatabaseConnectionFactory;
use Cicada\Core\Maintenance\System\Service\SetupDatabaseAdapter;
use Cicada\Core\Maintenance\System\Struct\DatabaseConnectionInformation;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SetupDatabaseAdapterTest extends TestCase
{
    public function testInitialize(): void
    {
        $connectionInfo = DatabaseConnectionInformation::fromEnv();

        $testDbName = 'test_schema';
        $connection = DatabaseConnectionFactory::createConnection($connectionInfo, true);
        $setupDatabaseAdapter = new SetupDatabaseAdapter();

        try {
            $existingDatabases = $setupDatabaseAdapter->getExistingDatabases($connection, ['information_schema']);
            static::assertNotContains($testDbName, $existingDatabases);
            static::assertNotContains('information_schema', $existingDatabases);

            $setupDatabaseAdapter->createDatabase($connection, $testDbName);

            static::assertContains($testDbName, $setupDatabaseAdapter->getExistingDatabases($connection, []));
            static::assertFalse($setupDatabaseAdapter->hasCicadaTables($connection, $testDbName));

            $setupDatabaseAdapter->initializeCicadaDb($connection, $testDbName);

            static::assertTrue($setupDatabaseAdapter->hasCicadaTables($connection, $testDbName));
        } finally {
            $setupDatabaseAdapter->dropDatabase($connection, $testDbName);

            static::assertNotContains($testDbName, $setupDatabaseAdapter->getExistingDatabases($connection, []));
        }
    }
}
