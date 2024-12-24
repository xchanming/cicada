<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Theme;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\PlatformRequest;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Theme\ConfigLoader\AbstractConfigLoader;
use Cicada\Storefront\Theme\MD5ThemePathBuilder;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\File as StorefrontPluginConfigurationFile;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\ThemeFileResolver;
use Cicada\Storefront\Theme\ThemeScripts;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
#[CoversClass(ThemeScripts::class)]
class ThemeScriptsTest extends TestCase
{
    public function testGetThemeScriptsWhenNoRequestGiven(): void
    {
        $themeScripts = new ThemeScripts(
            $this->createMock(StorefrontPluginRegistry::class),
            $this->createMock(ThemeFileResolver::class),
            $this->createMock(RequestStack::class),
            new MD5ThemePathBuilder(),
            new ArrayAdapter(),
            $this->createMock(AbstractConfigLoader::class)
        );

        static::assertEquals([], $themeScripts->getThemeScripts());
    }

    public function testGetThemeScriptsWhenAdminRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $themeScripts = new ThemeScripts(
            $this->createMock(StorefrontPluginRegistry::class),
            $this->createMock(ThemeFileResolver::class),
            $requestStack,
            new MD5ThemePathBuilder(),
            new ArrayAdapter(),
            $this->createMock(AbstractConfigLoader::class)
        );

        static::assertEquals([], $themeScripts->getThemeScripts());
    }

    public function testNotExistingTheme(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'invalid');
        $requestStack->push($request);

        $pluginRegistry = $this->createMock(StorefrontPluginRegistry::class);
        $pluginRegistry->method('getConfigurations')->willReturn(new StorefrontPluginConfigurationCollection([]));

        $themeScripts = new ThemeScripts(
            $pluginRegistry,
            $this->createMock(ThemeFileResolver::class),
            $requestStack,
            new MD5ThemePathBuilder(),
            new ArrayAdapter(),
            $this->createMock(AbstractConfigLoader::class)
        );

        static::assertEquals([], $themeScripts->getThemeScripts());
    }

    public function testLoadPaths(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, 'Storefront');
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_ID, 'Storefront');
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'Storefront');

        $salesChannelContext = Generator::createSalesChannelContext();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $salesChannelContext);

        $requestStack->push($request);

        $pluginRegistry = $this->createMock(StorefrontPluginRegistry::class);

        $storefront = new StorefrontPluginConfiguration('Storefront');
        $pluginRegistry->method('getConfigurations')->willReturn(new StorefrontPluginConfigurationCollection([$storefront]));

        $themeFileResolver = $this->createMock(ThemeFileResolver::class);
        $themeFileResolver
            ->expects(static::once())
            ->method('resolveFiles')
            ->willReturn([
                ThemeFileResolver::SCRIPT_FILES => new FileCollection([
                    new StorefrontPluginConfigurationFile('foo/foo.js', [], 'foo'),
                ]),
            ]);

        $themeScripts = new ThemeScripts(
            $pluginRegistry,
            $themeFileResolver,
            $requestStack,
            new MD5ThemePathBuilder(),
            new ArrayAdapter(),
            $this->createMock(AbstractConfigLoader::class)
        );

        static::assertEquals(['js/foo/foo.js'], $themeScripts->getThemeScripts());
        static::assertEquals(['js/foo/foo.js'], $themeScripts->getThemeScripts());
    }

    public function testInheritsFromBase(): void
    {
        $requestStack = new RequestStack();
        $request = new Request();

        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, 'Storefront');
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_ID, 'ChildId');
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'ChildName');
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_BASE_NAME, 'Storefront');
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, Context::createCLIContext());

        $salesChannelContext = Generator::createSalesChannelContext();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $salesChannelContext);

        $requestStack->push($request);

        $pluginRegistry = $this->createMock(StorefrontPluginRegistry::class);

        $storefront = new StorefrontPluginConfiguration('Storefront');
        $pluginRegistry->method('getConfigurations')->willReturn(new StorefrontPluginConfigurationCollection([$storefront]));

        $themeFileResolver = $this->createMock(ThemeFileResolver::class);
        $themeFileResolver
            ->method('resolveFiles')
            ->willReturn([
                ThemeFileResolver::SCRIPT_FILES => new FileCollection([
                    new StorefrontPluginConfigurationFile('foo/foo.js', [], 'foo'),
                ]),
            ]);

        $themeScripts = new ThemeScripts(
            $pluginRegistry,
            $themeFileResolver,
            $requestStack,
            new MD5ThemePathBuilder(),
            new ArrayAdapter(),
            $this->createMock(AbstractConfigLoader::class)
        );

        static::assertEquals(['js/foo/foo.js'], $themeScripts->getThemeScripts());
    }
}
