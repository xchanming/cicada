<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway;

use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Gateway\CheckoutGatewayResponse;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CheckoutGatewayResponse::class)]
#[Package('checkout')]
class CheckoutGatewayResponseTest extends TestCase
{
    public function testConstruct(): void
    {
        $response = new CheckoutGatewayResponse(
            $payments = new PaymentMethodCollection(),
            $shipments = new ShippingMethodCollection(),
            $errors = new ErrorCollection()
        );

        static::assertSame($payments, $response->getAvailablePaymentMethods());
        static::assertSame($shipments, $response->getAvailableShippingMethods());
        static::assertSame($errors, $response->getCartErrors());
    }

    public function testSetters(): void
    {
        $response = new CheckoutGatewayResponse(
            new PaymentMethodCollection(),
            new ShippingMethodCollection(),
            new ErrorCollection()
        );

        $response->setAvailablePaymentMethods($newPayments = new PaymentMethodCollection());
        $response->setAvailableShippingMethods($newShipments = new ShippingMethodCollection());
        $response->setCartErrors($newErrors = new ErrorCollection());

        static::assertSame($newPayments, $response->getAvailablePaymentMethods());
        static::assertSame($newShipments, $response->getAvailableShippingMethods());
        static::assertSame($newErrors, $response->getCartErrors());
    }
}
