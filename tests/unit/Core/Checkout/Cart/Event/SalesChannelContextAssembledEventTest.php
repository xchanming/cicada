<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Event\SalesChannelContextAssembledEvent;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SalesChannelContextAssembledEvent::class)]
class SalesChannelContextAssembledEventTest extends TestCase
{
    public function testConstruct(): void
    {
        $order = new OrderEntity();
        $context = Generator::generateSalesChannelContext();

        $event = new SalesChannelContextAssembledEvent($order, $context);

        static::assertSame($order, $event->getOrder());
        static::assertSame($context->getContext(), $event->getContext());
    }
}
