<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Checkout\Payload;

use Cicada\Core\Framework\App\Checkout\Payload\AppCheckoutGatewayPayload;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AppCheckoutGatewayPayload::class)]
#[Package('checkout')]
class AppCheckoutGatewayPayloadTest extends TestCase
{
    public function testApi(): void
    {
        $context = Generator::generateSalesChannelContext();
        $cart = Generator::createCart();
        $paymentMethods = ['paymentMethod-1', 'paymentMethod-2'];
        $shippingMethods = ['shippingMethod-1', 'shippingMethod-2'];
        $source = new Source('https://example.com', 'hatoken', '1.0.0');

        $payload = new AppCheckoutGatewayPayload($context, $cart, $paymentMethods, $shippingMethods);
        $payload->setSource($source);

        static::assertSame($context, $payload->getSalesChannelContext());
        static::assertSame($cart, $payload->getCart());
        static::assertSame($paymentMethods, $payload->getPaymentMethods());
        static::assertSame($shippingMethods, $payload->getShippingMethods());
        static::assertSame($source, $payload->getSource());
    }
}
