<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\PaymentHandler;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStateHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[Package('checkout')]
class RefundTestPaymentHandler implements RefundPaymentHandlerInterface
{
    public function __construct(private readonly OrderTransactionCaptureRefundStateHandler $stateHandler)
    {
    }

    public function refund(string $refundId, Context $context): void
    {
        $this->stateHandler->complete($refundId, $context);
    }
}
