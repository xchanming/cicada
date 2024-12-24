<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Cicada\Core\Framework\Util\Filesystem;
use Cicada\Core\Test\Stub\App\StaticSourceResolver;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory;
use Cicada\Tests\Unit\Storefront\Theme\fixtures\PluginWithAdditionalBundles\PluginWithAdditionalBundles;
use Cicada\Tests\Unit\Storefront\Theme\fixtures\ThemeAndPlugin\TestTheme\TestTheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StorefrontPluginConfigurationFactory::class)]
class StorefrontPluginConfigurationFactoryTest extends TestCase
{
    public function testGetDecoratedThrows(): void
    {
        $configurationFactory = new StorefrontPluginConfigurationFactory(
            $this->createMock(KernelPluginLoader::class),
            new StaticSourceResolver([])
        );

        static::expectException(DecorationPatternException::class);
        $configurationFactory->getDecorated();
    }

    public function testFactorySetsConfiguration(): void
    {
        $configurationFactory = new StorefrontPluginConfigurationFactory(
            $this->createMock(KernelPluginLoader::class),
            new StaticSourceResolver([])
        );

        $themePluginBundle = new TestTheme();

        $config = $configurationFactory->createFromBundle($themePluginBundle);

        static::assertEquals('TestTheme', $config->getName());
        static::assertEquals(
            [
                'name' => 'TestTheme',
                'author' => 'Cicada AG',
                'views' => [
                    '@Storefront',
                    '@Plugins',
                    '@TestTheme',
                ],
                'style' => [
                    'app/storefront/src/scss/overrides.scss',
                    '@Storefront',
                    'app/storefront/src/scss/base.scss',
                ],
                'script' => [
                    '@Storefront',
                    'app/storefront/dist/storefront/js/test-theme/test-theme.js',
                ],
                'asset' => [],
            ],
            $config->getThemeJson()
        );
        static::assertEmpty($config->getThemeConfig());
        static::assertTrue($config->getIsTheme());
        static::assertCount(3, $config->getStyleFiles());
        static::assertCount(2, $config->getScriptFiles());
        static::assertFalse($config->hasAdditionalBundles());
    }

    public function testFactorySetsConfigurationWithAdditionalBundles(): void
    {
        $configurationFactory = new StorefrontPluginConfigurationFactory(
            $this->createMock(KernelPluginLoader::class),
            new StaticSourceResolver([])
        );

        $PluginSubBundle = new PluginWithAdditionalBundles(true, '');

        $config = $configurationFactory->createFromBundle($PluginSubBundle);

        static::assertTrue($config->hasAdditionalBundles());
    }

    public function testFactorySetsConfigurationWithAppSource(): void
    {
        $configurationFactory = new StorefrontPluginConfigurationFactory(
            $this->createMock(KernelPluginLoader::class),
            new StaticSourceResolver(['test' => new Filesystem(__DIR__ . '/fixtures/Apps/test')])
        );

        $config = $configurationFactory->createFromApp('test', __DIR__ . '/fixtures/Apps/test');

        static::assertFalse($config->getIsTheme());
    }

    public function testFactorySetsConfigurationWithAppSourceAsTheme(): void
    {
        $configurationFactory = new StorefrontPluginConfigurationFactory(
            $this->createMock(KernelPluginLoader::class),
            new StaticSourceResolver(['SwagTheme' => new Filesystem(__DIR__ . '/fixtures/Apps/theme')])
        );

        $config = $configurationFactory->createFromApp('SwagTheme', __DIR__ . '/fixtures/Apps/theme');

        static::assertTrue($config->getIsTheme());
    }
}
