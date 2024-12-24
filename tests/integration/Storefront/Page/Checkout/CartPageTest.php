<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Checkout;

use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Gateway\SalesChannel\AbstractCheckoutGatewayRoute;
use Cicada\Core\Checkout\Gateway\SalesChannel\CheckoutGatewayRouteResponse;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\Country\SalesChannel\CountryRoute;
use Cicada\Storefront\Checkout\Cart\SalesChannel\StorefrontCartFacade;
use Cicada\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Cicada\Storefront\Page\Checkout\Cart\CheckoutCartPageLoader;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Test\Page\StorefrontPageTestBehaviour;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class CartPageTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontPageTestBehaviour;

    public function testItLoadsTheCart(): void
    {
        $request = new Request();
        $context = $this->createSalesChannelContextWithNavigation();

        $event = null;
        $this->catchEvent(CheckoutCartPageLoadedEvent::class, $event);

        $page = $this->getPageLoader()->load($request, $context);

        static::assertSame(0.0, $page->getCart()->getPrice()->getNetPrice());
        static::assertSame($context->getToken(), $page->getCart()->getToken());
        self::assertPageEvent(CheckoutCartPageLoadedEvent::class, $event, $context, $request, $page);
    }

    public function testAddsCurrentSelectedShippingMethod(): void
    {
        $response = new CheckoutGatewayRouteResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $route = $this->createMock(AbstractCheckoutGatewayRoute::class);
        $route
            ->method('load')
            ->willReturn($response);

        $loader = new CheckoutCartPageLoader(
            static::getContainer()->get(GenericPageLoader::class),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(StorefrontCartFacade::class),
            $route,
            static::getContainer()->get(CountryRoute::class)
        );

        $context = $this->createSalesChannelContextWithNavigation();

        $result = $loader->load(new Request(), $context);

        static::assertTrue($result->getShippingMethods()->has($context->getShippingMethod()->getId()));
    }

    protected function getPageLoader(): CheckoutCartPageLoader
    {
        return static::getContainer()->get(CheckoutCartPageLoader::class);
    }
}
