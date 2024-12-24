<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\Event\RecurringPaymentOrderCriteriaEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Annotation\DisabledFeatures;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[CoversClass(RecurringPaymentOrderCriteriaEvent::class)]
class RecurringPaymentOrderCriteriaEventTest extends TestCase
{
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testEvent(): void
    {
        $orderId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $criteria = new Criteria();

        $event = new RecurringPaymentOrderCriteriaEvent($orderId, $criteria, $context);

        static::assertSame($orderId, $event->getOrderId());
        static::assertSame($criteria, $event->getCriteria());
        static::assertSame($context, $event->getContext());
    }
}
