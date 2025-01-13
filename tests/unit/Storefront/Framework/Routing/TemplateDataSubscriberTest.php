<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Routing;

use Cicada\Core\Content\Seo\Hreflang\HreflangCollection;
use Cicada\Core\Content\Seo\HreflangLoaderInterface;
use Cicada\Core\Framework\App\ActiveAppsLoader;
use Cicada\Core\Framework\App\Exception\AppUrlChangeDetectedException;
use Cicada\Core\Framework\App\ShopId\ShopIdProvider;
use Cicada\Core\PlatformRequest;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Event\StorefrontRenderEvent;
use Cicada\Storefront\Framework\Routing\TemplateDataSubscriber;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;
use Cicada\Storefront\Theme\StorefrontPluginRegistry;
use Cicada\Storefront\Theme\StorefrontPluginRegistryInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(TemplateDataSubscriber::class)]
class TemplateDataSubscriberTest extends TestCase
{
    private HreflangLoaderInterface&MockObject $hreflangLoader;

    private ShopIdProvider&MockObject $shopIdProvider;

    private StorefrontPluginRegistryInterface&MockObject $themeRegistry;

    private ActiveAppsLoader&MockObject $activeAppsLoader;

    private TemplateDataSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->hreflangLoader = $this->createMock(HreflangLoaderInterface::class);
        $this->shopIdProvider = $this->createMock(ShopIdProvider::class);
        $this->themeRegistry = $this->createMock(StorefrontPluginRegistryInterface::class);
        $this->activeAppsLoader = $this->createMock(ActiveAppsLoader::class);

        $this->subscriber = new TemplateDataSubscriber(
            $this->hreflangLoader,
            $this->shopIdProvider,
            $this->themeRegistry,
            $this->activeAppsLoader
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $events = TemplateDataSubscriber::getSubscribedEvents();

        static::assertArrayHasKey(StorefrontRenderEvent::class, $events);

        static::assertArrayHasKey(StorefrontRenderEvent::class, $events);
        static::assertIsArray($events[StorefrontRenderEvent::class]);
        static::assertCount(3, $events[StorefrontRenderEvent::class]);

        static::assertArrayHasKey('0', $events[StorefrontRenderEvent::class]);
        static::assertIsArray($events[StorefrontRenderEvent::class][0]);
        static::assertArrayHasKey('0', $events[StorefrontRenderEvent::class][0]);
        static::assertEquals('addHreflang', $events[StorefrontRenderEvent::class][0][0]);

        static::assertArrayHasKey('1', $events[StorefrontRenderEvent::class]);
        static::assertIsArray($events[StorefrontRenderEvent::class][1]);
        static::assertEquals('addShopIdParameter', $events[StorefrontRenderEvent::class][1][0]);

        static::assertArrayHasKey('2', $events[StorefrontRenderEvent::class]);
        static::assertIsArray($events[StorefrontRenderEvent::class][2]);
        static::assertEquals('addIconSetConfig', $events[StorefrontRenderEvent::class][2][0]);
    }

    public function testAddHreflangWithNullRoute(): void
    {
        $event = new StorefrontRenderEvent(
            'test',
            [],
            new Request(),
            Generator::generateSalesChannelContext()
        );

        $this->hreflangLoader->expects(static::never())->method('load');

        $this->subscriber->addHreflang($event);
    }

    public function testAddHreflangWithValidRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'frontend.home');
        $request->attributes->set('_route_params', ['param' => 'value']);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, Generator::generateSalesChannelContext());

        $event = new StorefrontRenderEvent(
            'test',
            [],
            $request,
            Generator::generateSalesChannelContext()
        );

        $this->hreflangLoader
            ->expects(static::once())
            ->method('load')
            ->willReturn(new HreflangCollection());

        $this->subscriber->addHreflang($event);

        static::assertInstanceOf(HreflangCollection::class, $event->getParameters()['hrefLang']);
    }

    public function testAddShopIdParameterWithNoActiveApps(): void
    {
        $event = new StorefrontRenderEvent(
            'test',
            [],
            new Request(),
            Generator::generateSalesChannelContext()
        );

        $this->activeAppsLoader
            ->method('getActiveApps')
            ->willReturn([]);

        $this->shopIdProvider
            ->expects(static::never())
            ->method('getShopId');

        $this->subscriber->addShopIdParameter($event);
    }

    public function testAddShopIdParameterWithUrlChangeException(): void
    {
        $event = new StorefrontRenderEvent(
            'test',
            [],
            new Request(),
            Generator::generateSalesChannelContext()
        );

        $this->activeAppsLoader
            ->method('getActiveApps')
            ->willReturn(['someApp']);

        $this->shopIdProvider
            ->expects(static::once())
            ->method('getShopId')
            ->willThrowException(new AppUrlChangeDetectedException('before', 'new', '123'));

        $this->subscriber->addShopIdParameter($event);
    }

    public function testShopIdAdded(): void
    {
        $event = new StorefrontRenderEvent(
            'test',
            [],
            new Request(),
            Generator::generateSalesChannelContext()
        );

        $this->activeAppsLoader
            ->method('getActiveApps')
            ->willReturn(['someApp']);

        $this->shopIdProvider
            ->expects(static::once())
            ->method('getShopId')
            ->willReturn('123');

        $this->subscriber->addShopIdParameter($event);

        static::assertEquals('123', $event->getParameters()['appShopId']);
    }

    public function testAddIconSetConfigWithNoTheme(): void
    {
        $event = new StorefrontRenderEvent(
            'test',
            [],
            new Request(),
            Generator::generateSalesChannelContext()
        );

        $this->themeRegistry
            ->expects(static::never())
            ->method('getConfigurations');

        $this->subscriber->addIconSetConfig($event);
    }

    public function testAddIconSetConfigWithNoThemeButThemeName(): void
    {
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'Storefront');

        $event = new StorefrontRenderEvent(
            'test',
            [],
            $request,
            Generator::generateSalesChannelContext()
        );

        $this->themeRegistry
            ->expects(static::once())
            ->method('getConfigurations');

        $this->subscriber->addIconSetConfig($event);
        static::assertArrayNotHasKey('themeIconConfig', $event->getParameters());
    }

    public function testAddIconSetConfigWithValidTheme(): void
    {
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'Storefront');

        $event = new StorefrontRenderEvent(
            'test',
            [],
            $request,
            Generator::generateSalesChannelContext()
        );

        $themeConfig = new StorefrontPluginConfiguration('Storefront');
        $themeConfig->setIconSets(['default' => '@Storefront/icons/default']);

        $collection = new StorefrontPluginConfigurationCollection();
        $collection->add($themeConfig);

        $this->themeRegistry
            ->method('getConfigurations')
            ->willReturn($collection);

        $this->subscriber->addIconSetConfig($event);

        static::assertArrayHasKey('themeIconConfig', $event->getParameters());
    }

    public function testAddIconSetConfigWithRegistryWithGetByTechnicalName(): void
    {
        $request = new Request();
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_THEME_NAME, 'Storefront');

        $event = new StorefrontRenderEvent(
            'test',
            [],
            $request,
            Generator::generateSalesChannelContext()
        );

        $themeConfig = new StorefrontPluginConfiguration('Storefront');
        $themeConfig->setIconSets(['default' => '@Storefront/icons/default']);

        $themeRegistry = $this->createMock(StorefrontPluginRegistry::class);

        $themeRegistry
            ->expects(static::never())
            ->method('getConfigurations');

        $themeRegistry
            ->expects(static::once())
            ->method('getByTechnicalName')
            ->willReturn($themeConfig);

        $subscriber = new TemplateDataSubscriber(
            $this->hreflangLoader,
            $this->shopIdProvider,
            $themeRegistry,
            $this->activeAppsLoader
        );

        $subscriber->addIconSetConfig($event);

        static::assertArrayHasKey('themeIconConfig', $event->getParameters());
    }
}
