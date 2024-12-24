<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\CashPayment;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CashPayment::class)]
class CashPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);
        if (!\is_a(CashPayment::class, AbstractPaymentHandler::class, true)) {
            static::markTestSkipped(\sprintf('Class %s must extend %s', CashPayment::class, AbstractPaymentHandler::class));
        }
    }

    public function testPay(): void
    {
        $payment = new CashPayment();
        $reponse = $payment->pay(
            new Request(),
            new PaymentTransactionStruct(Uuid::randomHex()),
            Context::createDefaultContext(),
            null,
        );

        static::assertNull($reponse);
    }

    public function testSupports(): void
    {
        $payment = new CashPayment();

        foreach (PaymentHandlerType::cases() as $case) {
            static::assertFalse($payment->supports(
                $case,
                Uuid::randomHex(),
                Context::createDefaultContext()
            ));
        }
    }
}
