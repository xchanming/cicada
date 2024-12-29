<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('checkout')]
class PrePayment extends DefaultPayment
{
    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return $type === PaymentHandlerType::RECURRING;
    }

    public function recurring(PaymentTransactionStruct $transaction, Context $context): void
    {
    }
}
