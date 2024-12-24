<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('checkout')]
class PaymentTransactionStructFactory extends AbstractPaymentTransactionStructFactory
{
    public function getDecorated(): AbstractPaymentTransactionStructFactory
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, use `build` instead
     */
    public function sync(OrderTransactionEntity $orderTransaction, OrderEntity $order): SyncPaymentTransactionStruct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead');

        return new SyncPaymentTransactionStruct($orderTransaction, $order);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, use `build` instead
     */
    public function async(OrderTransactionEntity $orderTransaction, OrderEntity $order, string $returnUrl): AsyncPaymentTransactionStruct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead');

        return new AsyncPaymentTransactionStruct($orderTransaction, $order, $returnUrl);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, use `build` instead
     */
    public function prepared(OrderTransactionEntity $orderTransaction, OrderEntity $order): PreparedPaymentTransactionStruct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead');

        return new PreparedPaymentTransactionStruct($orderTransaction, $order);
    }

    /**
     * @deprecated tag:v6.7.0 - will be removed, use `build` instead
     */
    public function recurring(OrderTransactionEntity $orderTransaction, OrderEntity $order): RecurringPaymentTransactionStruct
    {
        Feature::triggerDeprecationOrThrow('v6.7.0.0', 'The payment process via interfaces is deprecated, extend the `AbstractPaymentHandler` instead');

        return new RecurringPaymentTransactionStruct($orderTransaction, $order);
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
