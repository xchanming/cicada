<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Store\Services;

use Cicada\Core\Framework\App\AppCollection;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\PluginCollection;
use Cicada\Core\Framework\Plugin\PluginService;
use Cicada\Core\Framework\Store\Services\ExtensionLoader;
use Cicada\Core\Framework\Store\Struct\BinaryCollection;
use Cicada\Core\Framework\Store\Struct\ExtensionStruct;
use Cicada\Core\Framework\Store\Struct\ImageCollection;
use Cicada\Core\Framework\Store\Struct\PermissionCollection;
use Cicada\Core\Framework\Store\Struct\PermissionStruct;
use Cicada\Core\Framework\Store\Struct\VariantCollection;
use Cicada\Core\Framework\Test\Store\ExtensionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Composer\IO\NullIO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('skip-paratest')]
#[Package('checkout')]
class ExtensionLoaderTest extends TestCase
{
    use ExtensionBehaviour;
    use IntegrationTestBehaviour;

    private ExtensionLoader $extensionLoader;

    protected function setUp(): void
    {
        $this->extensionLoader = static::getContainer()->get(ExtensionLoader::class);

        $this->registerPlugin(__DIR__ . '/../_fixtures/AppStoreTestPlugin');
        $this->installApp(__DIR__ . '/../_fixtures/TestApp');
    }

    protected function tearDown(): void
    {
        $this->removePlugin(__DIR__ . '/../_fixtures/AppStoreTestPlugin');
        $this->removeApp(__DIR__ . '/../_fixtures/TestApp');
    }

    public function testAppNotInstalledDetectedAsTheme(): void
    {
        $this->installApp(__DIR__ . '/../_fixtures/TestAppTheme', false);
        $extensions = $this->extensionLoader->loadFromAppCollection(
            Context::createDefaultContext(),
            new AppCollection([])
        );

        /** @var ExtensionStruct $extension */
        $extension = $extensions->get('TestAppTheme');
        static::assertTrue($extension->isTheme());
        $this->removeApp(__DIR__ . '/../_fixtures/TestAppTheme');
    }

    public function testLocalUpdateShouldSetLatestVersion(): void
    {
        $appManifestPath = static::getContainer()->getParameter('kernel.app_dir') . '/TestApp/manifest.xml';
        $appManifestXml = file_get_contents($appManifestPath);
        static::assertIsString($appManifestXml, 'Could not read manifest.xml file');
        file_put_contents($appManifestPath, str_replace('1.0.0', '1.0.1', $appManifestXml));

        $extensions = $this->extensionLoader->loadFromAppCollection(
            Context::createDefaultContext(),
            new AppCollection([$this->getInstalledApp()])
        );

        /** @var ExtensionStruct $extension */
        $extension = $extensions->get('TestApp');
        static::assertSame('1.0.0', $extension->getVersion());
        static::assertSame('1.0.1', $extension->getLatestVersion());
    }

    public function testItLoadsExtensionFromResponseLikeArray(): void
    {
        $listingResponse = $this->getDetailResponseFixture();

        $extension = $this->extensionLoader->loadFromArray(
            Context::createDefaultContext(),
            $listingResponse
        );

        static::assertNull($extension->getLocalId());
        static::assertNull($extension->getLicense());
        static::assertNull($extension->getVersion());
        static::assertEquals($listingResponse['name'], $extension->getName());
        static::assertEquals($listingResponse['label'], $extension->getLabel());

        static::assertInstanceOf(VariantCollection::class, $extension->getVariants());
        static::assertInstanceOf(ImageCollection::class, $extension->getImages());
        static::assertInstanceOf(BinaryCollection::class, $extension->getBinaries());
    }

    public function testLoadsExtensionsFromListingArray(): void
    {
        $listingResponse = $this->getListingResponseFixture();

        $extensions = $this->extensionLoader->loadFromListingArray(
            Context::createDefaultContext(),
            $listingResponse
        );

        static::assertCount(2, $extensions);
    }

    public function testItLoadsExtensionsFromPlugins(): void
    {
        static::getContainer()->get(PluginService::class)->refreshPlugins(Context::createDefaultContext(), new NullIO());

        /** @var PluginCollection $plugins */
        $plugins = static::getContainer()->get('plugin.repository')->search(new Criteria(), Context::createDefaultContext())->getEntities();

        $extensions = $this->extensionLoader->loadFromPluginCollection(Context::createDefaultContext(), $plugins);

        /** @var ExtensionStruct $extension */
        $extension = $extensions->get('AppStoreTestPlugin');

        static::assertNotNull($extension);
        static::assertEquals('AppStoreTestPlugin', $extension->getName());
    }

    public function testUpgradeAtMapsToUpdatedAtInStruct(): void
    {
        static::getContainer()->get(PluginService::class)->refreshPlugins(Context::createDefaultContext(), new NullIO());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'AppStoreTestPlugin'));

        $firstPluginId = static::getContainer()->get('plugin.repository')->searchIds($criteria, Context::createDefaultContext())->firstId();

        $time = new \DateTime();

        /** @var EntityRepository $pluginRepository */
        $pluginRepository = static::getContainer()->get('plugin.repository');
        $pluginRepository->update([
            [
                'id' => $firstPluginId,
                'upgradedAt' => $time,
            ],
        ], Context::createDefaultContext());

        /** @var EntityRepository<PluginCollection> $pluginRepository */
        $pluginRepository = static::getContainer()->get('plugin.repository');
        $firstPlugin = $pluginRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($firstPlugin);

        $extensions = $this->extensionLoader->loadFromPluginCollection(Context::createDefaultContext(), new PluginCollection([$firstPlugin]));

        static::assertSame($time->getTimestamp(), $extensions->first()?->getUpdatedAt()?->getTimestamp());
    }

    public function testItLoadsExtensionsFromAppsCollection(): void
    {
        $installedApp = $this->getInstalledApp();

        $extensions = $this->extensionLoader->loadFromAppCollection(
            Context::createDefaultContext(),
            new AppCollection([$installedApp])
        );

        static::assertEquals([
            '英国英语',
            '中文',
        ], $extensions->first()?->getLanguages());

        static::assertSame($installedApp->getUpdatedAt(), $extensions->first()?->getUpdatedAt());
        static::assertEquals(new PermissionCollection([
            PermissionStruct::fromArray(['entity' => 'product', 'operation' => 'create']),
            PermissionStruct::fromArray(['entity' => 'product', 'operation' => 'read']),
            PermissionStruct::fromArray(['entity' => 'additional_privileges', 'operation' => 'additional:privilege']),
        ]), $extensions->first()?->getPermissions());

        foreach ($extensions as $extension) {
            static::assertEquals(ExtensionStruct::EXTENSION_TYPE_APP, $extension->getType());
        }
    }

    private function getInstalledApp(): AppEntity
    {
        /** @var EntityRepository<AppCollection> $appRepository */
        $appRepository = static::getContainer()->get('app.repository');

        $criteria = new Criteria();
        $criteria->addAssociation('translations');

        $app = $appRepository->search($criteria, Context::createDefaultContext())->getEntities()->first();
        static::assertNotNull($app, 'Installed app not found');

        return $app;
    }

    /**
     * @return array<string, mixed>
     */
    private function getDetailResponseFixture(): array
    {
        $content = file_get_contents(__DIR__ . '/../_fixtures/responses/extension-detail.json');
        static::assertIsString($content, 'Could not read extension-detail.json file');

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function getListingResponseFixture(): array
    {
        $content = file_get_contents(__DIR__ . '/../_fixtures/responses/extension-listing.json');
        static::assertIsString($content, 'Could not read extension-listing.json file');

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }
}
