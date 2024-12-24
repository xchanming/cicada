<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order\SalesChannel;

use Cicada\Core\Checkout\Order\SalesChannel\SetPaymentOrderRouteResponse;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SetPaymentOrderRouteResponse::class)]
class SetPaymentOrderRouteResponseTest extends TestCase
{
    public function testPublicAPI(): void
    {
        $response = new SetPaymentOrderRouteResponse();
        $object = $response->getObject();

        static::assertInstanceOf(ArrayStruct::class, $object);
        static::assertTrue($object->get('success'));
    }
}
