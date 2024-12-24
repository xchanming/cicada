<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\Twig\ThemeInheritanceBuilder;

/**
 * @internal
 */
#[CoversClass(ThemeInheritanceBuilder::class)]
class ThemeInheritanceBuilderTest extends TestCase
{
    private ThemeInheritanceBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ThemeInheritanceBuilder(new TestStorefrontPluginRegistry(
            new StorefrontPluginConfigurationCollection([
                new StorefrontPluginConfiguration('Storefront'),
            ])
        ));
    }

    public function testBuildPreservesThePluginOrder(): void
    {
        $result = $this->builder->build([
            'ExtensionPlugin' => [],
            'BasePlugin' => [],
            'Storefront' => [],
        ], [
            'Storefront' => [],
        ]);

        static::assertSame([
            'ExtensionPlugin' => [],
            'BasePlugin' => [],
            'Storefront' => [],
        ], $result);
    }

    public function testSortBundlesByPriority(): void
    {
        $result = $this->builder->build([
            'Profiling' => -2,
            'Elasticsearch' => -1,
            'Administration' => -1,
            'Framework' => -1,
            'ExtensionPlugin' => 0,
            'Storefront' => 0,
        ], [
            'Storefront' => true,
        ]);

        static::assertSame([
            'ExtensionPlugin' => 0,
            'Elasticsearch' => -1,
            'Administration' => -1,
            'Framework' => -1,
            'Profiling' => -2,
            'Storefront' => 0,
        ], $result);
    }
}

/**
 * @internal
 */
class TestStorefrontPluginRegistry extends StorefrontPluginRegistry
{
    public function __construct(private readonly StorefrontPluginConfigurationCollection $plugins)
    {
    }

    public function getConfigurations(): StorefrontPluginConfigurationCollection
    {
        return $this->plugins;
    }
}
