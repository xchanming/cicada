<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\SalesChannel;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayInterface;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Gateway\Command\Struct\CheckoutGatewayPayloadStruct;
use Cicada\Core\Checkout\Gateway\SalesChannel\CheckoutGatewayRoute;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Cicada\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Rule\RuleIdMatcher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CheckoutGatewayRoute::class)]
#[Package('checkout')]
class CheckoutGatewayRouteTest extends TestCase
{
    public function testDecoratedThrows(): void
    {
        $route = new CheckoutGatewayRoute(
            $this->createMock(AbstractPaymentMethodRoute::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(CheckoutGatewayInterface::class),
            $this->createMock(RuleIdMatcher::class),
        );

        $this->expectException(DecorationPatternException::class);

        $route->getDecorated();
    }

    public function testLoad(): void
    {
        $request = new Request();
        $cart = new Cart('hatoken');
        $context = Generator::createSalesChannelContext();

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());

        $paymentMethods = new PaymentMethodRouteResponse(
            new EntitySearchResult(
                PaymentMethodDefinition::ENTITY_NAME,
                1,
                new PaymentMethodCollection([$paymentMethod]),
                null,
                new Criteria(),
                $context->getContext()
            )
        );

        $ruleId = Uuid::randomHex();
        $context->getContext()->setRuleIds([$ruleId]);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId(Uuid::randomHex());
        $shippingMethod->setAvailabilityRuleId($ruleId);

        $shippingMethods = new ShippingMethodRouteResponse(
            new EntitySearchResult(
                ShippingMethodDefinition::ENTITY_NAME,
                1,
                new ShippingMethodCollection([$shippingMethod]),
                null,
                new Criteria(),
                $context->getContext()
            )
        );

        $paymentMethodRoute = $this->createMock(AbstractPaymentMethodRoute::class);
        $paymentMethodRoute
            ->expects(static::once())
            ->method('load')
            ->with($request, $context, static::equalTo((new Criteria())->addAssociation('appPaymentMethod.app')))
            ->willReturn($paymentMethods);

        $shippingMethodRoute = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingMethodRoute
            ->expects(static::once())
            ->method('load')
            ->with($request, $context, static::equalTo((new Criteria())->addAssociation('appShippingMethod.app')))
            ->willReturn($shippingMethods);

        $response = new CheckoutGatewayResponse(
            $paymentMethods->getPaymentMethods(),
            $shippingMethods->getShippingMethods(),
            new ErrorCollection()
        );

        $payload = new CheckoutGatewayPayloadStruct($cart, $context, $paymentMethods->getPaymentMethods(), $shippingMethods->getShippingMethods());

        $checkoutGateway = $this->createMock(CheckoutGatewayInterface::class);
        $checkoutGateway
            ->expects(static::once())
            ->method('process')
            ->with(static::equalTo($payload))
            ->willReturn($response);

        $ruleIdMatcher = $this->createMock(RuleIdMatcher::class);
        $ruleIdMatcher
            ->expects(static::exactly(2))
            ->method('filterCollection')
            ->willReturnArgument(0);

        $route = new CheckoutGatewayRoute($paymentMethodRoute, $shippingMethodRoute, $checkoutGateway, $ruleIdMatcher);
        $result = $route->load($request, $cart, $context);

        static::assertSame($paymentMethods->getPaymentMethods(), $result->getPaymentMethods());
        static::assertSame($shippingMethods->getShippingMethods(), $result->getShippingMethods());
        static::assertSame($response->getCartErrors(), $result->getErrors());
    }

    #[DisabledFeatures(['v6.7.0.0'])]
    public function testLoadWithOnlyAvailableFlag(): void
    {
        $request = new Request();
        $cart = new Cart('hatoken');
        $context = Generator::createSalesChannelContext();

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());

        $paymentMethods = new PaymentMethodRouteResponse(
            new EntitySearchResult(
                PaymentMethodDefinition::ENTITY_NAME,
                1,
                new PaymentMethodCollection([$paymentMethod]),
                null,
                new Criteria(),
                $context->getContext()
            )
        );

        $ruleId = Uuid::randomHex();
        $context->getContext()->setRuleIds([$ruleId]);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId(Uuid::randomHex());
        $shippingMethod->setAvailabilityRuleId($ruleId);

        $shippingMethods = new ShippingMethodRouteResponse(
            new EntitySearchResult(
                ShippingMethodDefinition::ENTITY_NAME,
                1,
                new ShippingMethodCollection([$shippingMethod]),
                null,
                new Criteria(),
                $context->getContext()
            )
        );

        $paymentMethodRoute = $this->createMock(AbstractPaymentMethodRoute::class);
        $paymentMethodRoute
            ->expects(static::once())
            ->method('load')
            ->with($request, $context, static::equalTo((new Criteria())->addAssociation('appPaymentMethod.app')))
            ->willReturn($paymentMethods);

        $shippingMethodRoute = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingMethodRoute
            ->expects(static::once())
            ->method('load')
            ->with($request, $context, static::equalTo((new Criteria())->addAssociation('appShippingMethod.app')))
            ->willReturn($shippingMethods);

        $response = new CheckoutGatewayResponse(
            $paymentMethods->getPaymentMethods(),
            $shippingMethods->getShippingMethods(),
            new ErrorCollection()
        );

        $payload = new CheckoutGatewayPayloadStruct($cart, $context, $paymentMethods->getPaymentMethods(), $shippingMethods->getShippingMethods());

        $checkoutGateway = $this->createMock(CheckoutGatewayInterface::class);
        $checkoutGateway
            ->expects(static::once())
            ->method('process')
            ->with(static::equalTo($payload))
            ->willReturn($response);

        $ruleIdMatcher = $this->createMock(RuleIdMatcher::class);
        $ruleIdMatcher
            ->expects(static::exactly(2))
            ->method('filterCollection')
            ->willReturnArgument(0);

        $route = new CheckoutGatewayRoute($paymentMethodRoute, $shippingMethodRoute, $checkoutGateway, $ruleIdMatcher);
        $result = $route->load($request, $cart, $context);

        static::assertSame($paymentMethods->getPaymentMethods(), $result->getPaymentMethods());
        static::assertSame($shippingMethods->getShippingMethods(), $result->getShippingMethods());
        static::assertSame($response->getCartErrors(), $result->getErrors());
        static::assertSame('1', $request->query->get('onlyAvailable'));
    }

    public function testUnavailableMethodsAddCartError(): void
    {
        $request = new Request();
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId(Uuid::randomHex());
        $shippingMethod->addTranslated('name', 'Foo');

        $cart = new Cart('hatoken');
        $cart->addDeliveries(
            new DeliveryCollection([
                new Delivery(
                    new DeliveryPositionCollection(),
                    new DeliveryDate(new \DateTimeImmutable(), new \DateTimeImmutable()),
                    $shippingMethod,
                    new ShippingLocation(new CountryEntity(), null, null),
                    new CalculatedPrice(100.00, 100.00, new CalculatedTaxCollection(), new TaxRuleCollection())
                ),
            ])
        );

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->addTranslated('name', 'Bar');

        $context = Generator::createSalesChannelContext(paymentMethod: $paymentMethod);

        $paymentMethods = new PaymentMethodRouteResponse(
            new EntitySearchResult(
                PaymentMethodDefinition::ENTITY_NAME,
                0,
                new PaymentMethodCollection(),
                null,
                new Criteria(),
                $context->getContext()
            )
        );

        $shippingMethods = new ShippingMethodRouteResponse(
            new EntitySearchResult(
                ShippingMethodDefinition::ENTITY_NAME,
                0,
                new ShippingMethodCollection(),
                null,
                new Criteria(),
                $context->getContext()
            )
        );

        $paymentMethodRoute = $this->createMock(AbstractPaymentMethodRoute::class);
        $paymentMethodRoute
            ->expects(static::once())
            ->method('load')
            ->with($request, $context, static::equalTo((new Criteria())->addAssociation('appPaymentMethod.app')))
            ->willReturn($paymentMethods);

        $shippingMethodRoute = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingMethodRoute
            ->expects(static::once())
            ->method('load')
            ->with($request, $context, static::equalTo((new Criteria())->addAssociation('appShippingMethod.app')))
            ->willReturn($shippingMethods);

        $response = new CheckoutGatewayResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $payload = new CheckoutGatewayPayloadStruct($cart, $context, $paymentMethods->getPaymentMethods(), $shippingMethods->getShippingMethods());

        $checkoutGateway = $this->createMock(CheckoutGatewayInterface::class);
        $checkoutGateway
            ->expects(static::once())
            ->method('process')
            ->with(static::equalTo($payload))
            ->willReturn($response);

        $ruleIdMatcher = $this->createMock(RuleIdMatcher::class);
        $ruleIdMatcher
            ->expects(static::exactly(2))
            ->method('filterCollection')
            ->willReturnArgument(0);

        $route = new CheckoutGatewayRoute(
            $paymentMethodRoute,
            $shippingMethodRoute,
            $checkoutGateway,
            $ruleIdMatcher
        );

        $result = $route->load($request, $cart, $context);

        static::assertCount(2, $result->getErrors());

        $error = $result->getErrors()->first();

        static::assertNotNull($error);
        static::assertSame('payment-method-blocked', $error->getMessageKey());
        static::assertSame('Payment method Bar not available. Reason: not allowed', $error->getMessage());

        $error = $result->getErrors()->last();

        static::assertNotNull($error);
        static::assertSame('shipping-method-blocked', $error->getMessageKey());
        static::assertSame('Shipping method Foo not available', $error->getMessage());
    }
}
