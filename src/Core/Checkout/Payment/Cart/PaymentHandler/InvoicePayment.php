<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;

if (Feature::isActive('v6.7.0.0')) {
    /**
     * @internal
     */
    #[Package('checkout')]
    class InvoicePayment extends DefaultPayment
    {
        public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
        {
            return $type === PaymentHandlerType::RECURRING;
        }

        public function recurring(PaymentTransactionStruct $transaction, Context $context): void
        {
        }
    }
} else {
    /**
     * @deprecated tag:v6.7.0 - reason:becomes-internal
     */
    #[Package('checkout')]
    class InvoicePayment extends DefaultPayment implements RecurringPaymentHandlerInterface
    {
        public function captureRecurring(RecurringPaymentTransactionStruct $transaction, Context $context): void
        {
        }
    }
}
