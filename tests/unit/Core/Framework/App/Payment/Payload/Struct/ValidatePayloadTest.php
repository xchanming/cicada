<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Payload\Struct;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\App\Payment\Payload\Struct\CapturePayload;
use Cicada\Core\Framework\App\Payment\Payload\Struct\ValidatePayload;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CapturePayload::class)]
class ValidatePayloadTest extends TestCase
{
    public function testPayload(): void
    {
        $cart = new Cart('testToken');
        $requestData = ['foo' => 'bar'];
        $salesChannelContext = Generator::generateSalesChannelContext();
        $source = new Source('foo', 'bar', '1.0.0');

        $payload = new ValidatePayload($cart, $requestData, $salesChannelContext);
        $payload->setSource($source);

        static::assertSame($cart, $payload->getCart());
        static::assertSame($requestData, $payload->getRequestData());
        static::assertSame($salesChannelContext, $payload->getSalesChannelContext());
        static::assertSame($source, $payload->getSource());
    }
}
