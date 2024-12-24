<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Event;

use Cicada\Core\Checkout\Payment\Event\PayPaymentOrderCriteriaEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(PayPaymentOrderCriteriaEvent::class)]
class PayPaymentOrderCriteriaEventTest extends TestCase
{
    public function testEvent(): void
    {
        $orderId = Uuid::randomHex();
        $context = Generator::createSalesChannelContext();
        $criteria = new Criteria();

        $event = new PayPaymentOrderCriteriaEvent($orderId, $criteria, $context);

        static::assertSame($orderId, $event->getOrderId());
        static::assertSame($criteria, $event->getCriteria());
        static::assertSame($context, $event->getContext());
    }
}
