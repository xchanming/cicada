<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

/**
 * @deprecated tag:v6.7.0 - will be removed, extend AbstractPaymentHandler instead
 */
#[Package('checkout')]
interface RefundPaymentHandlerInterface extends PaymentHandlerInterface
{
    public function refund(string $refundId, Context $context): void;
}
