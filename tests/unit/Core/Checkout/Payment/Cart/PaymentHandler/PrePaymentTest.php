<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PrePayment::class)]
class PrePaymentTest extends TestCase
{
    public function testPay(): void
    {
        $payment = new PrePayment();
        $response = $payment->pay(
            new Request(),
            new PaymentTransactionStruct(Uuid::randomHex()),
            Context::createDefaultContext(),
            null,
        );

        static::assertNull($response);
    }

    public function testSupports(): void
    {
        $payment = new PrePayment();

        foreach (PaymentHandlerType::cases() as $case) {
            $supports = $payment->supports(
                $case,
                Uuid::randomHex(),
                Context::createDefaultContext()
            );

            static::assertSame($case === PaymentHandlerType::RECURRING, $supports);
        }
    }

    #[DoesNotPerformAssertions]
    public function testRecurring(): void
    {
        $payment = new PrePayment();
        $payment->recurring(
            new PaymentTransactionStruct(Uuid::randomHex()),
            Context::createDefaultContext(),
        );
    }
}
