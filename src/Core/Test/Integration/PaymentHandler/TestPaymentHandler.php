<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\PaymentHandler;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Cicada\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @codeCoverageIgnore this is only a fixture for the payment handler integration tests
 */
#[Package('checkout')]
class TestPaymentHandler extends AbstractPaymentHandler
{
    final public const REDIRECT_URL = 'https://xchanming.com';

    public function __construct(private readonly OrderTransactionStateHandler $transactionStateHandler)
    {
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return false;
    }

    public function validate(
        Cart $cart,
        RequestDataBag $dataBag,
        SalesChannelContext $context
    ): ?Struct {
        if ($dataBag->getBoolean('fail')) {
            throw PaymentException::validatePreparedPaymentInterrupted('this is supposed to fail');
        }

        return new ArrayStruct(['testValue']);
    }

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct): ?RedirectResponse
    {
        if ($request->request->getBoolean('fail')) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransactionId(),
                'Async Test Payment failed'
            );
        }

        $this->transactionStateHandler->process($transaction->getOrderTransactionId(), $context);

        if ($request->request->getBoolean('noredirect')) {
            return null;
        }

        return new RedirectResponse(self::REDIRECT_URL);
    }

    public function finalize(Request $request, PaymentTransactionStruct $transaction, Context $context): void
    {
        if ($request->query->getBoolean('cancel')) {
            throw PaymentException::customerCanceled(
                $transaction->getOrderTransactionId(),
                'Async Test Payment canceled'
            );
        }

        if ($request->query->getBoolean('fail')) {
            throw PaymentException::asyncFinalizeInterrupted(
                $transaction->getOrderTransactionId(),
                'Async Test Payment failed'
            );
        }

        $this->transactionStateHandler->paid($transaction->getOrderTransactionId(), $context);
    }
}
