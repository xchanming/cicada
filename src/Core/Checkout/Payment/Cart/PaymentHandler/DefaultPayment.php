<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

if (Feature::isActive('v6.7.0.0')) {
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
} else {
    /**
     * @deprecated tag:v6.7.0 - reason:becomes-internal
     */
    #[Package('checkout')]
    class DefaultPayment implements SynchronousPaymentHandlerInterface
    {
        /**
         * @var OrderTransactionStateHandler
         *
         * @deprecated tag:v6.7.0 - will be removed for DefaultPayments
         */
        protected $transactionStateHandler;

        /**
         * @internal
         */
        public function __construct(OrderTransactionStateHandler $transactionStateHandler)
        {
            $this->transactionStateHandler = $transactionStateHandler;
        }

        public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
        {
            // needed for payment methods like Cash on delivery and Paid in advance
        }
    }
}
