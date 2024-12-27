<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart;

use Cicada\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentTransactionStructFactory::class)]
class PaymentTransactionStructFactoryTest extends TestCase
{
    public function testDecorated(): void
    {
        static::expectException(DecorationPatternException::class);

        $factory = new PaymentTransactionStructFactory();
        $factory->getDecorated();
    }

    public function testDecoration(): void
    {
        $factory = new class extends PaymentTransactionStructFactory {
            public function getDecorated(): AbstractPaymentTransactionStructFactory
            {
                return new static();
            }
        };

        static::assertInstanceOf(PaymentTransactionStructFactory::class, $factory->getDecorated());

        $struct = $factory->build('transaction-id', Context::createDefaultContext(), 'https://return.url');

        static::assertSame('transaction-id', $struct->getOrderTransactionId());
        static::assertSame('https://return.url', $struct->getReturnUrl());
    }

    public function testBuild(): void
    {
        $factory = new PaymentTransactionStructFactory();
        $struct = $factory->build('transaction-id', Context::createDefaultContext(), 'https://return.url');

        static::assertSame('transaction-id', $struct->getOrderTransactionId());
        static::assertSame('https://return.url', $struct->getReturnUrl());
    }

    public function testRefund(): void
    {
        $factory = new PaymentTransactionStructFactory();
        $struct = $factory->refund('refund-id', 'transaction-id');

        static::assertSame('refund-id', $struct->getRefundId());
        static::assertSame('transaction-id', $struct->getOrderTransactionId());
    }
}
