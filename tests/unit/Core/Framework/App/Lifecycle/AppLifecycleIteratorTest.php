<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Lifecycle;

use Cicada\Core\Framework\App\Lifecycle\AbstractAppLifecycle;
use Cicada\Core\Framework\App\Lifecycle\AppLifecycleIterator;
use Cicada\Core\Framework\App\Lifecycle\AppLoader;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\PartialEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AppLifecycleIterator::class)]
class AppLifecycleIteratorTest extends TestCase
{
    public function testInstallMissingApp(): void
    {
        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->method('load')->willReturn([
            'ValidManifestApp' => Manifest::createFromXmlFile(__DIR__ . '/_fixtures/appDirValidationTest/ValidManifestApp/manifest.xml'),
        ]);

        $lifecycle = new AppLifecycleIterator(
            new StaticEntityRepository([new EntityCollection(), new EntityCollection()]),
            $appLoader
        );

        $appLifecycle = $this->createMock(AbstractAppLifecycle::class);
        $appLifecycle->expects(static::once())->method('install');

        $lifecycle->iterateOverApps(
            $appLifecycle,
            true,
            Context::createCLIContext()
        );
    }

    public function testUpdate(): void
    {
        $existingApp = new PartialEntity();
        $existingApp->setUniqueIdentifier('ValidManifestApp');
        $existingApp->set('id', 'ValidManifestApp');
        $existingApp->set('name', 'ValidManifestApp');
        $existingApp->set('version', '0.0.0');
        $existingApp->set('aclRoleId', '1234');

        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->method('load')->willReturn([
            'ValidManifestApp' => Manifest::createFromXmlFile(__DIR__ . '/_fixtures/appDirValidationTest/ValidManifestApp/manifest.xml'),
        ]);

        $lifecycle = new AppLifecycleIterator(
            new StaticEntityRepository([new EntityCollection([$existingApp]), new EntityCollection([$existingApp])]),
            $appLoader
        );

        $appLifecycle = $this->createMock(AbstractAppLifecycle::class);
        $appLifecycle->expects(static::never())->method('install');
        $appLifecycle->expects(static::once())->method('update');

        $lifecycle->iterateOverApps(
            $appLifecycle,
            true,
            Context::createCLIContext()
        );
    }

    public function testInstalledAppSkipped(): void
    {
        $existingApp = new PartialEntity();
        $existingApp->setUniqueIdentifier('ValidManifestApp');
        $existingApp->set('id', 'ValidManifestApp');
        $existingApp->set('name', 'ValidManifestApp');
        $existingApp->set('version', '1.0.0');
        $existingApp->set('aclRoleId', '1234');

        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->method('load')->willReturn([
            'ValidManifestApp' => Manifest::createFromXmlFile(__DIR__ . '/_fixtures/appDirValidationTest/ValidManifestApp/manifest.xml'),
        ]);

        $lifecycle = new AppLifecycleIterator(
            new StaticEntityRepository([new EntityCollection([$existingApp]), new EntityCollection([$existingApp])]),
            $appLoader
        );

        $appLifecycle = $this->createMock(AbstractAppLifecycle::class);
        $appLifecycle->expects(static::never())->method('install');
        $appLifecycle->expects(static::never())->method('update');

        $lifecycle->iterateOverApps(
            $appLifecycle,
            true,
            Context::createCLIContext()
        );
    }

    public function testAppGetsRemovedWhenNotOnDisk(): void
    {
        $existingApp = new PartialEntity();
        $existingApp->setUniqueIdentifier('ValidManifestApp');
        $existingApp->set('id', 'ValidManifestApp');
        $existingApp->set('name', 'ValidManifestApp');
        $existingApp->set('version', '1.0.0');
        $existingApp->set('aclRoleId', '1234');

        $appLoader = $this->createMock(AppLoader::class);

        $lifecycle = new AppLifecycleIterator(
            new StaticEntityRepository([new EntityCollection([$existingApp]), new EntityCollection([$existingApp])]),
            $appLoader
        );

        $appLifecycle = $this->createMock(AbstractAppLifecycle::class);
        $appLifecycle->expects(static::never())->method('install');
        $appLifecycle->expects(static::never())->method('update');
        $appLifecycle->expects(static::once())->method('delete');

        $lifecycle->iterateOverApps(
            $appLifecycle,
            true,
            Context::createCLIContext()
        );
    }

    public function testRefreshSpecificOneDoesNotDeleteOthers(): void
    {
        $existingApp = new PartialEntity();
        $existingApp->setUniqueIdentifier('ValidManifestApp');
        $existingApp->set('id', 'ValidManifestApp');
        $existingApp->set('name', 'ValidManifestApp');
        $existingApp->set('version', '1.0.0');
        $existingApp->set('aclRoleId', '1234');

        $appLoader = $this->createMock(AppLoader::class);

        $lifecycle = new AppLifecycleIterator(
            new StaticEntityRepository([new EntityCollection([$existingApp]), new EntityCollection([$existingApp])]),
            $appLoader
        );

        $appLifecycle = $this->createMock(AbstractAppLifecycle::class);
        $appLifecycle->expects(static::never())->method('install');
        $appLifecycle->expects(static::never())->method('update');
        $appLifecycle->expects(static::never())->method('delete');

        $lifecycle->iterateOverApps(
            $appLifecycle,
            true,
            Context::createCLIContext(),
            ['Foo']
        );
    }

    public function testInstallationException(): void
    {
        $appLoader = $this->createMock(AppLoader::class);
        $appLoader->method('load')->willReturn([
            'ValidManifestApp' => Manifest::createFromXmlFile(__DIR__ . '/_fixtures/appDirValidationTest/ValidManifestApp/manifest.xml'),
        ]);

        $lifecycle = new AppLifecycleIterator(
            new StaticEntityRepository([new EntityCollection(), new EntityCollection()]),
            $appLoader
        );

        $appLifecycle = $this->createMock(AbstractAppLifecycle::class);
        $appLifecycle->expects(static::once())->method('install')->willThrowException(new \Exception('Test'));

        $fails = $lifecycle->iterateOverApps(
            $appLifecycle,
            true,
            Context::createCLIContext()
        );

        static::assertNotEmpty($fails);
        static::assertCount(1, $fails);
    }
}
