<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme\StorefrontPluginConfiguration;

use Cicada\Core\Framework\Bundle;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Framework\ThemeInterface;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\AbstractStorefrontPluginConfigurationFactory;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class StorefrontPluginConfigurationFactoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    private AbstractStorefrontPluginConfigurationFactory $configFactory;

    protected function setUp(): void
    {
        $this->configFactory = static::getContainer()->get(StorefrontPluginConfigurationFactory::class);
    }

    public function testCreateThemeConfig(): void
    {
        $basePath = realpath(__DIR__ . '/../fixtures/ThemeConfig');
        static::assertIsString($basePath);

        $theme = $this->getBundle('TestTheme', $basePath, true);
        $config = $this->configFactory->createFromBundle($theme);

        static::assertEquals('TestTheme', $config->getTechnicalName());
        static::assertTrue($config->getIsTheme());
        static::assertEquals(
            'app/storefront/src/main.js',
            $config->getStorefrontEntryFilepath()
        );
        $this->assertFileCollection([
            'app/storefront/src/scss/overrides.scss' => [],
            '@Storefront' => [],
            'app/storefront/src/scss/base.scss' => [
                'vendor' => 'app/storefront/vendor',
            ],
        ], $config->getStyleFiles());
        $this->assertFileCollection([
            '@Storefront' => [],
            'app/storefront/dist/js/main.js' => [],
        ], $config->getScriptFiles());
        static::assertEquals([
            '@Storefront',
            '@Plugins',
            '@SwagTheme',
        ], $config->getViewInheritance());
        static::assertEquals(['app/storefront/dist/assets'], $config->getAssetPaths());
        static::assertEquals('app/storefront/dist/assets/preview.jpg', $config->getPreviewMedia());
        static::assertEquals([
            'fields' => [
                'sw-image' => [
                    'type' => 'media',
                    'value' => 'app/storefront/dist/assets/test.jpg',
                ],
            ],
        ], $config->getThemeConfig());
        static::assertEquals([
            'custom-icons' => 'app/storefront/src/assets/icon-pack/custom-icons',
        ], $config->getIconSets());
    }

    public function testPluginHasSingleScssEntryPoint(): void
    {
        $basePath = realpath(__DIR__ . '/../fixtures/SimplePlugin');
        static::assertIsString($basePath);
        $bundle = $this->getBundle('SimplePlugin', $basePath);

        $config = $this->configFactory->createFromBundle($bundle);

        $this->assertFileCollection(['app/storefront/src/scss/base.scss' => []], $config->getStyleFiles());
    }

    public function testPluginHasNoScssEntryPoint(): void
    {
        $basePath = realpath(__DIR__ . '/../fixtures/SimplePluginWithoutCompilation');
        static::assertIsString($basePath);

        $bundle = $this->getBundle('SimplePluginWithoutCompilation', $basePath);
        $config = $this->configFactory->createFromBundle($bundle);

        $this->assertFileCollection([], $config->getStyleFiles());
    }

    public function testPluginHasNoScssEntryPointButDifferentScssFiles(): void
    {
        $basePath = realpath(__DIR__ . '/../fixtures/SimpleWithoutStyleEntryPoint');
        static::assertIsString($basePath);

        $bundle = $this->getBundle('SimpleWithoutStyleEntryPoint', $basePath);

        $config = $this->configFactory->createFromBundle($bundle);

        // Style files should still be empty because of missing base.scss
        $this->assertFileCollection([], $config->getStyleFiles());
    }

    private function getBundle(string $name, string $basePath, bool $isTheme = false): Bundle
    {
        if ($isTheme) {
            return new class($name, $basePath) extends Bundle implements ThemeInterface {
                public function __construct(
                    string $name,
                    string $basePath
                ) {
                    $this->name = $name;
                    $this->path = $basePath;
                }
            };
        }

        return new class($name, $basePath) extends Bundle {
            public function __construct(
                string $name,
                string $basePath
            ) {
                $this->name = $name;
                $this->path = $basePath;
            }
        };
    }

    /**
     * @param array<string, array<string, string>> $expected
     */
    private function assertFileCollection(array $expected, FileCollection $files): void
    {
        $flatFiles = [];
        foreach ($files as $file) {
            $flatFiles[$file->getFilepath()] = $file->getResolveMapping();
        }

        static::assertEquals($expected, $flatFiles);
    }
}
