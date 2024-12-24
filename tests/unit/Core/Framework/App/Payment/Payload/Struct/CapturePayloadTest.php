<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Payment\Payload\Struct;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Cicada\Core\Framework\App\Payload\Source;
use Cicada\Core\Framework\App\Payment\Payload\Struct\CapturePayload;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CapturePayload::class)]
class CapturePayloadTest extends TestCase
{
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testPayload(): void
    {
        $transaction = new OrderTransactionEntity();
        $order = new OrderEntity();
        $preOrder = new ArrayStruct(['foo' => 'bar']);
        $recurring = new RecurringDataStruct('foo', new \DateTime());
        $source = new Source('foo', 'bar', '1.0.0');

        $payload = new CapturePayload($transaction, $order, $preOrder, $recurring);
        $payload->setSource($source);

        static::assertEquals($transaction, $payload->getOrderTransaction());
        static::assertSame($order, $payload->getOrder());
        static::assertSame($preOrder, $payload->getPreOrderPayment());
        static::assertSame($recurring, $payload->getRecurring());
        static::assertSame($source, $payload->getSource());
    }
}
