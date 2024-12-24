<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Payment\Cart\PaymentHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\DebitPayment;
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
#[CoversClass(DebitPayment::class)]
class DebitPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        Feature::skipTestIfInActive('v6.7.0.0', $this);
        if (!\is_a(DebitPayment::class, AbstractPaymentHandler::class, true)) {
            static::markTestSkipped(\sprintf('Class %s must extend %s', DebitPayment::class, AbstractPaymentHandler::class));
        }
    }

    public function testPay(): void
    {
        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $payment = new DebitPayment();
        $reponse = $payment->pay(
            new Request(),
            new PaymentTransactionStruct($transactionId),
            $context,
            null,
        );

        static::assertNull($reponse);
    }

    public function testSupports(): void
    {
        $payment = new DebitPayment();

        foreach (PaymentHandlerType::cases() as $case) {
            static::assertFalse($payment->supports(
                $case,
                Uuid::randomHex(),
                Context::createDefaultContext()
            ));
        }
    }
}
