<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Gateway\Command\Struct;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Gateway\Command\Struct\CheckoutGatewayPayloadStruct;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CheckoutGatewayPayloadStruct::class)]
#[Package('checkout')]
class CheckoutGatewayPayloadStructTest extends TestCase
{
    public function testConstruct(): void
    {
        $cart = new Cart('test');
        $context = Generator::generateSalesChannelContext();
        $paymentMethods = new PaymentMethodCollection();
        $shippingMethods = new ShippingMethodCollection();

        $struct = new CheckoutGatewayPayloadStruct($cart, $context, $paymentMethods, $shippingMethods);

        static::assertSame($cart, $struct->getCart());
        static::assertSame($context, $struct->getSalesChannelContext());
        static::assertSame($paymentMethods, $struct->getPaymentMethods());
        static::assertSame($shippingMethods, $struct->getShippingMethods());
    }
}
