<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\SalesChannel\OrderRouteResponse;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderRouteResponse::class)]
class OrderRouteResponseTest extends TestCase
{
    public function testPublicAPI(): void
    {
        $object = new EntitySearchResult(
            'order',
            0,
            new OrderCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $response = new OrderRouteResponse($object);
        $response->addPaymentChangeable(['foo' => true]);
        $response->addPaymentChangeable(['bar' => false]);

        static::assertEquals(
            new ArrayStruct(
                [
                    'orders' => $object,
                    'paymentChangeable' => ['foo' => true, 'bar' => false],
                ],
                'order-route-response-struct'
            ),
            $response->getObject()
        );

        static::assertSame($object, $response->getOrders());
        static::assertSame(['foo' => true, 'bar' => false], $response->getPaymentsChangeable());

        $response->setPaymentChangeable(['baz' => true]);

        static::assertEquals(
            new ArrayStruct(
                [
                    'orders' => $object,
                    'paymentChangeable' => ['baz' => true],
                ],
                'order-route-response-struct'
            ),
            $response->getObject()
        );
        static::assertSame(['baz' => true], $response->getPaymentsChangeable());
    }
}
