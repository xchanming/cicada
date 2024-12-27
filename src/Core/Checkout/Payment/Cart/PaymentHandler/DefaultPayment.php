<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class DefaultPayment extends AbstractPaymentHandler
{
    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        // needed for payment methods like Cash on delivery and Paid in advance
        return null;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }
}
