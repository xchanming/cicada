---
title: Unifed refund handler
issue: NEXT-18543
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com
author_github: @lernhart
---
# Core
* Implemented refund handling
* Added `Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture\OrderTransactionCaptureDefinition` to store captured transactions.
* Added `Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundDefinition` to store refunds of captured refunds.
* Added `Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefundPosition\OrderTransactionCaptureRefundPositionDefinition` to store a single position of refunds. 
* Added `Cicada\Core\Checkout\Payment\Cart\PaymentHandler\RefundPaymentHandlerInterface` to implement for payment methods to enable refund handling.
* Added `Cicada\Core\Checkout\Payment\Cart\PaymentRefundProcessor` to call payment refund handlers.
* Added various exceptions in the `Cicada\Core\Checkout\Payment\Exception\` namespace to throw on refund errors.
* Added method `Cicada\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry::getRefundHandlerForPaymentMethod` to get a refund handler for a payment method
* Added runtime field `refundable` in `Cicada\Core\Checkout\Payment\PaymentMethodDefinition`, which will return `true` on refundable payment methods.
* Added state machine `Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture\OrderTransactionCaptureStates`.
* Added state machine `Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefund\OrderTransactionCaptureRefundStates`.
___
# API
* Added route `api.action.order.order_transaction_capture_refund` to handle refund requests.
