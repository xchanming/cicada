<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @deprecated tag:v6.7.0 - will be removed, extend AbstractPaymentHandler instead
 */
#[Package('checkout')]
interface SynchronousPaymentHandlerInterface extends PaymentHandlerInterface
{
    /**
     * The pay function will be called after the customer completed the order.
     * Allows to process the order and store additional information.
     *
     * Throw a @see PaymentException exception if an error ocurres while processing the payment
     *
     * @throws PaymentException
     */
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void;
}
