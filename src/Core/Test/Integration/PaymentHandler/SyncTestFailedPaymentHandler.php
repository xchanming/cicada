<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\PaymentHandler;

use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[Package('checkout')]
class SyncTestFailedPaymentHandler implements SynchronousPaymentHandlerInterface
{
    public function pay(SyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): void
    {
        throw PaymentException::syncProcessInterrupted($transaction->getOrderTransaction()->getId(), 'This is a TestPaymentHandler which will always fail');
    }
}
