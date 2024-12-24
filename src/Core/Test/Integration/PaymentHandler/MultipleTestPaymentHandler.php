<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\PaymentHandler;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PreparedPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PreparedPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[Package('checkout')]
class MultipleTestPaymentHandler implements SynchronousPaymentHandlerInterface, PreparedPaymentHandlerInterface
{
    public function validate(
        Cart $cart,
        RequestDataBag $requestDataBag,
        SalesChannelContext $context
    ): Struct {
        return new ArrayStruct();
    }

    public function capture(
        PreparedPaymentTransactionStruct $transaction,
        RequestDataBag $requestDataBag,
        SalesChannelContext $context,
        Struct $preOrderPaymentStruct
    ): void {
    }

    public function pay(
        SyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): void {
    }
}
