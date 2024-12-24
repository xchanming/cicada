<?php declare(strict_types=1);

namespace Cicada\Core\Test\Integration\PaymentHandler;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PreparedPaymentHandlerInterface;
use Cicada\Core\Checkout\Payment\Cart\PreparedPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\PaymentException;
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
class PreparedTestPaymentHandler implements PreparedPaymentHandlerInterface
{
    final public const TEST_STRUCT_CONTENT = ['testValue'];

    public static ?Struct $preOrderPaymentStruct = null;

    public static bool $fail = false;

    public function validate(
        Cart $cart,
        RequestDataBag $requestDataBag,
        SalesChannelContext $context
    ): Struct {
        if (self::$fail) {
            throw PaymentException::validatePreparedPaymentInterrupted('this is supposed to fail');
        }

        self::$preOrderPaymentStruct = null;

        return new ArrayStruct(self::TEST_STRUCT_CONTENT);
    }

    public function capture(
        PreparedPaymentTransactionStruct $transaction,
        RequestDataBag $requestDataBag,
        SalesChannelContext $context,
        Struct $preOrderPaymentStruct
    ): void {
        if (self::$fail) {
            throw PaymentException::capturePreparedException($transaction->getOrderTransaction()->getId(), 'this is supposed to fail');
        }

        self::$preOrderPaymentStruct = $preOrderPaymentStruct;
    }
}
