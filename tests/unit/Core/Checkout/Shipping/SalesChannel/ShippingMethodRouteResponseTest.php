<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping\SalesChannel;

use Cicada\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingMethodRouteResponse::class)]
class ShippingMethodRouteResponseTest extends TestCase
{
    public function testConstruct(): void
    {
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setUniqueIdentifier('foo');

        $result = new EntitySearchResult(
            'shipping-method',
            1,
            $collection = new ShippingMethodCollection([$shippingMethod]),
            null,
            new Criteria(),
            Generator::generateSalesChannelContext()->getContext()
        );

        $response = new ShippingMethodRouteResponse($result);

        static::assertSame($collection, $response->getShippingMethods());
    }
}
