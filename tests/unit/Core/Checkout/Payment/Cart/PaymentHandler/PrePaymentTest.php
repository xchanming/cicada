<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\PaymentHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment;
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
#[CoversClass(PrePayment::class)]
class PrePaymentTest extends TestCase
{
    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);
        if (!\is_a(PrePayment::class, AbstractPaymentHandler::class, true)) {
            static::markTestSkipped(\sprintf('Class %s must extend %s', PrePayment::class, AbstractPaymentHandler::class));
        }
    }

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
