<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\Checkout\Offcanvas;

use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Cicada\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Cicada\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Cicada\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Cicada\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoader;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Page\MetaInformation;
use Cicada\Storefront\Page\Page;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(OffcanvasCartPageLoader::class)]
class OffcanvasCartPageLoaderTest extends TestCase
{
    public function testOffcanvasCartPageReturned(): void
    {
        $pageLoader = $this->createMock(GenericPageLoader::class);
        $pageLoader
            ->method('load')
            ->willReturn(new Page());

        $offcanvasCartPageLoader = new OffcanvasCartPageLoader(
            $this->createMock(EventDispatcher::class),
            $this->createMock(StorefrontCartFacade::class),
            $pageLoader,
            $this->createMock(ShippingMethodRoute::class)
        );

        static::expectNotToPerformAssertions();

        $page = $offcanvasCartPageLoader->load(
            new Request(),
            $this->createMock(SalesChannelContext::class)
        );
    }

    public function testRobotsMetaSetIfGiven(): void
    {
        $page = new OffcanvasCartPage();
        $page->setMetaInformation(new MetaInformation());

        $pageLoader = $this->createMock(GenericPageLoader::class);
        $pageLoader
            ->method('load')
            ->willReturn($page);

        $offcanvasCartPageLoader = new OffcanvasCartPageLoader(
            $this->createMock(EventDispatcher::class),
            $this->createMock(StorefrontCartFacade::class),
            $pageLoader,
            $this->createMock(ShippingMethodRoute::class)
        );

        $page = $offcanvasCartPageLoader->load(
            new Request(),
            $this->createMock(SalesChannelContext::class)
        );

        static::assertNotNull($page->getMetaInformation());
        static::assertSame('noindex,follow', $page->getMetaInformation()->getRobots());
    }

    #[DisabledFeatures(['v6.5.0.0'])]
    public function testRobotsMetaNotSetIfGiven(): void
    {
        $page = new OffcanvasCartPage();

        $pageLoader = $this->createMock(GenericPageLoader::class);
        $pageLoader
            ->method('load')
            ->willReturn($page);

        $offcanvasCartPageLoader = new OffcanvasCartPageLoader(
            $this->createMock(EventDispatcher::class),
            $this->createMock(StorefrontCartFacade::class),
            $pageLoader,
            $this->createMock(ShippingMethodRoute::class)
        );

        $page = $offcanvasCartPageLoader->load(
            new Request(),
            $this->createMock(SalesChannelContext::class)
        );

        static::assertNull($page->getMetaInformation());
    }

    public function testShippingMethodsAreSetToPage(): void
    {
        $shippingMethods = new ShippingMethodCollection([
            (new ShippingMethodEntity())->assign(['_uniqueIdentifier' => Uuid::randomHex()]),
            (new ShippingMethodEntity())->assign(['_uniqueIdentifier' => Uuid::randomHex()]),
        ]);

        $shippingMethodResponse = new ShippingMethodRouteResponse(
            new EntitySearchResult(
                ShippingMethodDefinition::ENTITY_NAME,
                2,
                $shippingMethods,
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $shippingMethodRoute = $this->createMock(ShippingMethodRoute::class);
        $shippingMethodRoute
            ->method('load')
            ->withAnyParameters()
            ->willReturn($shippingMethodResponse);

        $offcanvasCartPageLoader = new OffcanvasCartPageLoader(
            $this->createMock(EventDispatcher::class),
            $this->createMock(StorefrontCartFacade::class),
            $this->createMock(GenericPageLoader::class),
            $shippingMethodRoute,
        );

        $page = $offcanvasCartPageLoader->load(
            new Request(),
            $this->createMock(SalesChannelContext::class)
        );

        static::assertSame($shippingMethods, $page->getShippingMethods());
    }

    public function testValidationEventIsDispatched(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(OffcanvasCartPageLoadedEvent::class));

        $offcanvasCartPageLoader = new OffcanvasCartPageLoader(
            $eventDispatcher,
            $this->createMock(StorefrontCartFacade::class),
            $this->createMock(GenericPageLoader::class),
            $this->createMock(ShippingMethodRoute::class)
        );

        $offcanvasCartPageLoader->load(
            new Request(),
            $this->createMock(SalesChannelContext::class)
        );
    }
}
