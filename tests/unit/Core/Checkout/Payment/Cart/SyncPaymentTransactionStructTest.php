<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Cicada\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed
 */
#[Package('checkout')]
#[CoversClass(SyncPaymentTransactionStruct::class)]
class SyncPaymentTransactionStructTest extends TestCase
{
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testGetters(): void
    {
        $transaction = new OrderTransactionEntity();
        $order = new OrderEntity();
        $recurring = new RecurringDataStruct('foo', new \DateTime());

        $struct = new SyncPaymentTransactionStruct($transaction, $order, $recurring);

        static::assertSame($transaction, $struct->getOrderTransaction());
        static::assertSame($order, $struct->getOrder());
        static::assertSame($recurring, $struct->getRecurring());
        static::assertTrue($struct->isRecurring());
    }
}
