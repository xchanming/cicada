<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\SalesChannel;

use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Gateway\SalesChannel\CheckoutGatewayRouteResponse;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CheckoutGatewayRouteResponse::class)]
#[Package('checkout')]
class CheckoutGatewayRouteResponseTest extends TestCase
{
    public function testConstruct(): void
    {
        $response = new CheckoutGatewayRouteResponse(
            $payments = new PaymentMethodCollection(),
            $shipments = new ShippingMethodCollection(),
            $errors = new ErrorCollection()
        );

        static::assertSame($payments, $response->getPaymentMethods());
        static::assertSame($shipments, $response->getShippingMethods());
        static::assertSame($errors, $response->getErrors());
    }

    public function testSetters(): void
    {
        $response = new CheckoutGatewayRouteResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $response->setPaymentMethods($newPayments = new PaymentMethodCollection());
        $response->setShippingMethods($newShipments = new ShippingMethodCollection());
        $response->setErrors($newErrors = new ErrorCollection());

        static::assertSame($newPayments, $response->getPaymentMethods());
        static::assertSame($newShipments, $response->getShippingMethods());
        static::assertSame($newErrors, $response->getErrors());
    }
}
