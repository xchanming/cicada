<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Theme;

use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class StorefrontPluginRegistryTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    public function testConfigIsAddedIfItsATheme(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/fixtures/Apps/theme');

        $registry = static::getContainer()
            ->get(StorefrontPluginRegistry::class);

        static::assertInstanceOf(
            StorefrontPluginConfiguration::class,
            $registry->getConfigurations()->getByTechnicalName('SwagTheme')
        );
    }

    public function testConfigIsNotAddedIfAppIsNotActive(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/fixtures/Apps/theme', false);

        $registry = static::getContainer()
            ->get(StorefrontPluginRegistry::class);

        static::assertNull(
            $registry->getConfigurations()->getByTechnicalName('SwagTheme')
        );
    }

    public function testConfigIsAddedIfHasResourcesToCompile(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/fixtures/Apps/noThemeCustomCss');

        $registry = static::getContainer()
            ->get(StorefrontPluginRegistry::class);

        static::assertInstanceOf(
            StorefrontPluginConfiguration::class,
            $registry->getConfigurations()->getByTechnicalName('SwagNoThemeCustomCss')
        );
    }

    public function testConfigIsNotAddedButIdentifiedAsNotThemeIfItsNotATheme(): void
    {
        $this->loadAppsFromDir(__DIR__ . '/fixtures/Apps/noThemeNoCss');

        $registry = static::getContainer()
            ->get(StorefrontPluginRegistry::class);

        static::assertInstanceOf(
            StorefrontPluginConfiguration::class,
            $registry->getConfigurations()->getByTechnicalName('SwagNoThemeNoCss')
        );

        static::assertNull(
            $registry->getConfigurations()->getThemes()->getByTechnicalName('SwagNoThemeNoCss')
        );
    }
}
