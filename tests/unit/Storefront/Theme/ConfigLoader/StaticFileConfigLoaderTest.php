<?php
declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\ConfigLoader;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Theme\ConfigLoader\StaticFileConfigLoader;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('storefront')]
#[CoversClass(StaticFileConfigLoader::class)]
class StaticFileConfigLoaderTest extends TestCase
{
    public function testFileNotExisting(): void
    {
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Cannot find theme configuration. Did you run bin/console theme:dump');

        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $s = new StaticFileConfigLoader($fs);
        $s->load(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testBuild(): void
    {
        $id = Uuid::randomHex();

        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $fs->write('theme-config/' . $id . '.json', (string) file_get_contents(__DIR__ . '/../fixtures/ConfigLoader/theme-config.json'));

        $s = new StaticFileConfigLoader($fs);
        $config = $s->load($id, Context::createDefaultContext());

        $themeConfig = $config->getThemeConfig();
        static::assertIsArray($themeConfig);
        static::assertSame(
            [
                'blocks',
                'fields',
                'sw-color-brand-primary',
                'sw-color-brand-secondary',
                'sw-border-color',
                'sw-background-color',
                'sw-color-success',
                'sw-color-info',
                'sw-color-warning',
                'sw-color-danger',
                'sw-font-family-base',
                'sw-text-color',
                'sw-font-family-headline',
                'sw-headline-color',
                'sw-color-price',
                'sw-color-buy-button',
                'sw-color-buy-button-text',
                'sw-logo-desktop',
                'sw-logo-tablet',
                'sw-logo-mobile',
                'sw-logo-share',
                'sw-logo-favicon',
            ],
            array_keys($themeConfig)
        );
    }

    public function testCallGetDecoratedThrowsError(): void
    {
        static::expectException(DecorationPatternException::class);

        $fs = new Filesystem(new InMemoryFilesystemAdapter());
        $s = new StaticFileConfigLoader($fs);
        $s->getDecorated();
    }
}
