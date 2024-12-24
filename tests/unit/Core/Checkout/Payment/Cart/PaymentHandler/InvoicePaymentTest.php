<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\PaymentHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(InvoicePayment::class)]
class InvoicePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);
        if (!\is_a(InvoicePayment::class, AbstractPaymentHandler::class, true)) {
            static::markTestSkipped(\sprintf('Class %s must extend %s', InvoicePayment::class, AbstractPaymentHandler::class));
        }
    }

    public function testPay(): void
    {
        $payment = new InvoicePayment();
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
        $payment = new InvoicePayment();

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
        $payment = new InvoicePayment();
        $payment->recurring(
            new PaymentTransactionStruct(Uuid::randomHex()),
            Context::createDefaultContext(),
        );
    }
}
