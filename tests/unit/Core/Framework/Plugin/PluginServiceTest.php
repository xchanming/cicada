<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Plugin;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Plugin\PluginCollection;
use Cicada\Core\Framework\Plugin\PluginService;
use Cicada\Core\Framework\Plugin\Struct\PluginFromFileSystemStruct;
use Cicada\Core\Framework\Plugin\Util\PluginFinder;
use Cicada\Core\Framework\Plugin\Util\VersionSanitizer;
use Cicada\Core\System\Language\LanguageCollection;
use Cicada\Core\System\Language\LanguageEntity;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Composer\IO\IOInterface;
use Composer\Package\CompleteAliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\Version\VersionParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PluginService::class)]
class PluginServiceTest extends TestCase
{
    public function testPluginsAddsPlugin(): void
    {
        $pluginFinder = $this->createMock(PluginFinder::class);
        $completePackage = $this->getComposerPackage();

        $pluginFromFileSystemStruct = new PluginFromFileSystemStruct();
        $pluginFromFileSystemStruct->assign([
            'baseClass' => 'foo',
            'path' => __DIR__,
            'composerPackage' => $completePackage,
            'managedByComposer' => true,
        ]);

        $pluginFinder
            ->method('findPlugins')
            ->willReturn([
                $pluginFromFileSystemStruct,
            ]);

        /** @var StaticEntityRepository<PluginCollection> $pluginRepo */
        $pluginRepo = new StaticEntityRepository([new PluginCollection()]);
        $pluginService = new PluginService(
            __DIR__,
            __DIR__,
            $pluginRepo,
            $this->getLanguageRepository(),
            $pluginFinder,
            new VersionSanitizer()
        );

        $pluginService->refreshPlugins(Context::createDefaultContext(), $this->createMock(IOInterface::class));

        $upserts = $pluginRepo->upserts;
        static::assertCount(1, $upserts, 'There should be one plugin upserted');
        static::assertArrayHasKey('0', $upserts);

        static::assertCount(1, $upserts['0']);

        $pluginWrite = $upserts['0']['0'];

        static::assertSame('foo', $pluginWrite['name']);
        static::assertSame('foo', $pluginWrite['baseClass']);
        static::assertSame('foo', $pluginWrite['composerName']);
        static::assertSame('1.0.0', $pluginWrite['version']);
    }

    public function testPluginsAliasesGetResolved(): void
    {
        $pluginFinder = $this->createMock(PluginFinder::class);
        $pluginFromFileSystemStruct = new PluginFromFileSystemStruct();
        $completePackage = $this->getComposerPackage();

        $package = new CompleteAliasPackage($completePackage, VersionParser::DEFAULT_BRANCH_ALIAS, VersionParser::DEFAULT_BRANCH_ALIAS);

        $pluginFromFileSystemStruct->assign([
            'baseClass' => 'foo',
            'path' => __DIR__,
            'composerPackage' => $package,
            'managedByComposer' => true,
        ]);

        $pluginFinder
            ->method('findPlugins')
            ->willReturn([
                $pluginFromFileSystemStruct,
            ]);

        /** @var StaticEntityRepository<PluginCollection> $pluginRepo */
        $pluginRepo = new StaticEntityRepository([new PluginCollection()]);
        $pluginService = new PluginService(
            __DIR__,
            __DIR__,
            $pluginRepo,
            $this->getLanguageRepository(),
            $pluginFinder,
            new VersionSanitizer()
        );

        $pluginService->refreshPlugins(Context::createDefaultContext(), $this->createMock(IOInterface::class));

        $upserts = $pluginRepo->upserts;
        static::assertCount(1, $upserts, 'There should be one plugin upserted');
        static::assertArrayHasKey('0', $upserts);

        static::assertCount(1, $upserts['0']);

        $pluginWrite = $upserts['0']['0'];

        static::assertSame('foo', $pluginWrite['name']);
        static::assertSame('foo', $pluginWrite['baseClass']);
        static::assertSame('foo', $pluginWrite['composerName']);
        static::assertSame('1.0.0', $pluginWrite['version']);
    }

    /**
     * @return StaticEntityRepository<LanguageCollection>
     */
    private function getLanguageRepository(): StaticEntityRepository
    {
        $language = new LanguageEntity();
        $language->setId('foo');

        // @phpstan-ignore-next-line
        return new StaticEntityRepository([new LanguageCollection([$language]), new LanguageCollection([$language])]);
    }

    private function getComposerPackage(): CompletePackage
    {
        $completePackage = new CompletePackage('foo', '1.0.0', '1.0.0');
        $completePackage->setAutoload([
            'psr-4' => [
                'Foo\\' => 'bar',
            ],
        ]);
        $completePackage->setExtra([
            'label' => [
                'en-GB' => 'foo',
            ],
            'description' => [
                'en-GB' => 'foo',
            ],
        ]);

        return $completePackage;
    }
}
