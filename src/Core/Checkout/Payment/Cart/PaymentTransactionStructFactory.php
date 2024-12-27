<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('checkout')]
class PaymentTransactionStructFactory extends AbstractPaymentTransactionStructFactory
{
    public function getDecorated(): AbstractPaymentTransactionStructFactory
    {
        throw new DecorationPatternException(self::class);
    }

    public function build(string $orderTransactionId, Context $context, ?string $returnUrl = null): PaymentTransactionStruct
    {
        return new PaymentTransactionStruct($orderTransactionId, $returnUrl);
    }

    public function refund(string $refundId, string $orderTransactionId): RefundPaymentTransactionStruct
    {
        return new RefundPaymentTransactionStruct($refundId, $orderTransactionId);
    }
}
