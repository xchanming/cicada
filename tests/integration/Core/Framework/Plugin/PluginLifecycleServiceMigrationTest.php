<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Plugin;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Migration\MigrationCollection;
use Cicada\Core\Framework\Migration\MigrationCollectionLoader;
use Cicada\Core\Framework\Migration\MigrationSource;
use Cicada\Core\Framework\Plugin\Composer\CommandExecutor;
use Cicada\Core\Framework\Plugin\KernelPluginCollection;
use Cicada\Core\Framework\Plugin\PluginCollection;
use Cicada\Core\Framework\Plugin\PluginEntity;
use Cicada\Core\Framework\Plugin\PluginLifecycleService;
use Cicada\Core\Framework\Plugin\PluginService;
use Cicada\Core\Framework\Plugin\Requirement\RequirementsValidator;
use Cicada\Core\Framework\Plugin\Util\AssetService;
use Cicada\Core\Framework\Plugin\Util\PluginFinder;
use Cicada\Core\Framework\Plugin\Util\VersionSanitizer;
use Cicada\Core\Framework\Test\Migration\MigrationTestBehaviour;
use Cicada\Core\Framework\Test\Plugin\PluginTestsHelper;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\Kernel;
use Cicada\Core\System\CustomEntity\CustomEntityLifecycleService;
use Cicada\Core\System\CustomEntity\Schema\CustomEntityPersister;
use Cicada\Core\System\CustomEntity\Schema\CustomEntitySchemaUpdater;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Composer\IO\NullIO;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 */
#[Group('slow')]
#[Group('skip-paratest')]
class PluginLifecycleServiceMigrationTest extends TestCase
{
    use KernelTestBehaviour;
    use MigrationTestBehaviour;
    use PluginTestsHelper;

    private ContainerInterface $container;

    /**
     * @var EntityRepository<PluginCollection>
     */
    private EntityRepository $pluginRepo;

    private PluginService $pluginService;

    private Connection $connection;

    private PluginLifecycleService $pluginLifecycleService;

    private Context $context;

    private string $fixturePath;

    public static function tearDownAfterClass(): void
    {
        $connection = Kernel::getConnection();

        $connection->executeStatement('DELETE FROM migration WHERE `class` LIKE "SwagManualMigrationTest%"');
        $connection->executeStatement('DELETE FROM plugin');

        KernelLifecycleManager::bootKernel();
    }

    protected function setUp(): void
    {
        // force kernel boot
        KernelLifecycleManager::bootKernel();

        $this->container = static::getContainer();
        $this->pluginRepo = $this->container->get('plugin.repository');
        $this->connection = $this->container->get(Connection::class);
        $this->pluginLifecycleService = $this->createPluginLifecycleService();
        $this->context = Context::createDefaultContext();

        $this->fixturePath = __DIR__ . '/../../../../../src/Core/Framework/Test/Plugin/_fixture/';

        $this->pluginService = $this->createPluginService(
            $this->fixturePath . 'plugins',
            $this->container->getParameter('kernel.project_dir'),
            $this->pluginRepo,
            $this->container->get('language.repository'),
            $this->container->get(PluginFinder::class)
        );

        $this->addTestPluginToKernel(
            $this->fixturePath . 'plugins/SwagManualMigrationTestPlugin',
            'SwagManualMigrationTestPlugin'
        );
        $this->requireMigrationFiles();

        $this->pluginService->refreshPlugins($this->context, new NullIO());
        $this->connection->executeStatement('DELETE FROM plugin WHERE `name` = "SwagTest"');
    }

    public function testInstall(): MigrationCollection
    {
        static::assertSame(0, $this->connection->getTransactionNestingLevel());

        $migrationPlugin = $this->getMigrationTestPlugin();
        static::assertNull($migrationPlugin->getInstalledAt());

        $this->pluginLifecycleService->installPlugin($migrationPlugin, $this->context);
        $migrationCollection = $this->getMigrationCollection('SwagManualMigrationTestPlugin');
        $this->assertMigrationState($migrationCollection, 4, 1);

        return $migrationCollection;
    }

    #[Depends('testInstall')]
    public function testActivate(MigrationCollection $migrationCollection): MigrationCollection
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->activatePlugin($migrationPlugin, $this->context);
        $this->assertMigrationState($migrationCollection, 4, 2);

        return $migrationCollection;
    }

    #[Depends('testActivate')]
    public function testUpdate(MigrationCollection $migrationCollection): MigrationCollection
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->updatePlugin($migrationPlugin, $this->context);
        $this->assertMigrationState($migrationCollection, 4, 3, 1);

        return $migrationCollection;
    }

    #[Depends('testUpdate')]
    public function testDeactivate(MigrationCollection $migrationCollection): MigrationCollection
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->deactivatePlugin($migrationPlugin, $this->context);
        $this->assertMigrationState($migrationCollection, 4, 3, 1);

        return $migrationCollection;
    }

    #[Depends('testDeactivate')]
    public function testUninstallKeepUserData(MigrationCollection $migrationCollection): void
    {
        $migrationPlugin = $this->getMigrationTestPlugin();
        $this->pluginLifecycleService->uninstallPlugin($migrationPlugin, $this->context, true);
        $this->assertMigrationCount($migrationCollection, 4);
    }

    private function assertMigrationCount(MigrationCollection $migrationCollection, int $expectedCount): void
    {
        $connection = static::getContainer()->get(Connection::class);

        /** @var MigrationSource $migrationSource */
        $migrationSource = ReflectionHelper::getPropertyValue($migrationCollection, 'migrationSource');

        $dbMigrations = $connection
            ->fetchAllAssociative(
                'SELECT * FROM `migration` WHERE `class` REGEXP :pattern ORDER BY `creation_timestamp`',
                ['pattern' => $migrationSource->getNamespacePattern()]
            );

        TestCase::assertCount($expectedCount, $dbMigrations);
    }

    private function createPluginLifecycleService(): PluginLifecycleService
    {
        return new PluginLifecycleService(
            $this->pluginRepo,
            $this->container->get('event_dispatcher'),
            $this->container->get(KernelPluginCollection::class),
            $this->container->get('service_container'),
            $this->container->get(MigrationCollectionLoader::class),
            $this->container->get(AssetService::class),
            $this->container->get(CommandExecutor::class),
            $this->container->get(RequirementsValidator::class),
            $this->container->get('cache.messenger.restart_workers_signal'),
            Kernel::CICADA_FALLBACK_VERSION,
            $this->container->get(SystemConfigService::class),
            $this->container->get(CustomEntityPersister::class),
            $this->container->get(CustomEntitySchemaUpdater::class),
            $this->container->get(CustomEntityLifecycleService::class),
            $this->container->get(PluginService::class),
            $this->container->get(VersionSanitizer::class),
        );
    }

    private function getMigrationTestPlugin(): PluginEntity
    {
        return $this->pluginService
            ->getPluginByName('SwagManualMigrationTestPlugin', $this->context);
    }

    private function requireMigrationFiles(): void
    {
        require_once $this->fixturePath . 'plugins/SwagManualMigrationTestPlugin/src/Migration/Migration1.php';
        require_once $this->fixturePath . 'plugins/SwagManualMigrationTestPlugin/src/Migration/Migration2.php';
        require_once $this->fixturePath . 'plugins/SwagManualMigrationTestPlugin/src/Migration/Migration3.php';
        require_once $this->fixturePath . 'plugins/SwagManualMigrationTestPlugin/src/Migration/Migration4.php';
    }
}
