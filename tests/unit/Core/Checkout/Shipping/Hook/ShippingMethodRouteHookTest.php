<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Shipping\Hook;

use Cicada\Core\Checkout\Shipping\Hook\ShippingMethodRouteHook;
use Cicada\Core\Checkout\Shipping\ShippingMethodCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingMethodRouteHook::class)]
class ShippingMethodRouteHookTest extends TestCase
{
    public function testConstruct(): void
    {
        $hook = new ShippingMethodRouteHook(
            $collection = new ShippingMethodCollection(),
            true,
            $salesChannelContext = Generator::generateSalesChannelContext()
        );

        static::assertSame($collection, $hook->getCollection());
        static::assertTrue($hook->isOnlyAvailable());
        static::assertSame($salesChannelContext, $hook->getSalesChannelContext());

        static::assertSame('shipping-method-route-request', $hook->getName());
    }
}
