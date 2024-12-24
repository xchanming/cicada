<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Payment\Cart\PreparedPaymentTransactionStruct;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @deprecated tag:v6.7.0 - will be removed, extend AbstractPaymentHandler instead. Capture is now included into `pay`
 */
#[Package('checkout')]
interface PreparedPaymentHandlerInterface extends PaymentHandlerInterface
{
    /**
     * The validate method will be called before actually placing the order.
     * It allows the validation of the supplied payment transaction.
     *
     * @throws PaymentException
     */
    public function validate(
        Cart $cart,
        RequestDataBag $requestDataBag,
        SalesChannelContext $context
    ): Struct;

    /**
     * The capture method will be called, after successfully validating the payment before
     *
     * @throws PaymentException - PaymentException::PAYMENT_CAPTURE_PREPARED_ERROR
     */
    public function capture(
        PreparedPaymentTransactionStruct $transaction,
        RequestDataBag $requestDataBag,
        SalesChannelContext $context,
        Struct $preOrderPaymentStruct
    ): void;
}
