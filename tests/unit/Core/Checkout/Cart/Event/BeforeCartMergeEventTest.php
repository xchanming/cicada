<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Event\BeforeCartMergeEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BeforeCartMergeEvent::class)]
class BeforeCartMergeEventTest extends TestCase
{
    public function testReturnsCorrectProperties(): void
    {
        $customerCart = new Cart('customerCart');
        $guestCart = new Cart('customerCart');
        $mergeableLineItems = new LineItemCollection();
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $context = Context::createDefaultContext();
        $salesChannelContext->method('getContext')->willReturn($context);

        $event = new BeforeCartMergeEvent(
            $customerCart,
            $guestCart,
            $mergeableLineItems,
            $salesChannelContext
        );

        static::assertSame($customerCart, $event->getCustomerCart());
        static::assertSame($guestCart, $event->getGuestCart());
        static::assertSame($mergeableLineItems, $event->getMergeableLineItems());
        static::assertSame($salesChannelContext, $event->getSalesChannelContext());
        static::assertSame($context, $event->getContext());
    }
}
