<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\PaymentHandler;

use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Cicada\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - will be removed with new payment handlers
 */
#[Package('checkout')]
class AsyncTestPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    final public const REDIRECT_URL = 'https://cicada.com';

    public function __construct(private readonly OrderTransactionStateHandler $transactionStateHandler)
    {
    }

    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $context = $salesChannelContext->getContext();

        $this->transactionStateHandler->process($transaction->getOrderTransaction()->getId(), $context);

        return new RedirectResponse(self::REDIRECT_URL);
    }

    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {
        $context = $salesChannelContext->getContext();

        if ($request->query->getBoolean('cancel')) {
            throw PaymentException::customerCanceled(
                $transaction->getOrderTransaction()->getId(),
                'Async Test Payment canceled'
            );
        }

        $this->transactionStateHandler->paid($transaction->getOrderTransaction()->getId(), $context);
    }
}
