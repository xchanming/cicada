<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\Event\FinalizePaymentOrderTransactionCriteriaEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;

/**
 * @internal
 */
#[CoversClass(FinalizePaymentOrderTransactionCriteriaEvent::class)]
class FinalizePaymentOrderTransactionCriteriaEventTest extends TestCase
{
    public function testEvent(): void
    {
        $transactionId = Uuid::randomHex();
        $context = Generator::createSalesChannelContext();
        $criteria = new Criteria();

        $event = new FinalizePaymentOrderTransactionCriteriaEvent($transactionId, $criteria, $context);

        static::assertSame($transactionId, $event->getOrderTransactionId());
        static::assertSame($criteria, $event->getCriteria());
        static::assertSame($context, $event->getContext());
    }
}
