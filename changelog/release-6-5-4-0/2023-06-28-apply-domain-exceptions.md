---
title: Apply domain exceptions
issue: NEXT-28610
---
# Core
* Added new domain exception for payment `Cicada\Core\Checkout\Payment\PaymentException`
* Added new exception methods `orderNotFound`, `documentNotFound` and `generationError` for `Cicada\Core\Checkout\Document\DocumentException`
* Added new exception methods `invalidPaymentOrderNotStored` and `orderNotFound` for `Cicada\Core\Checkout\Cart\CartException`
* Added new exception method `paymentMethodNotAvailable` for `Cicada\Core\Checkout\Order\OrderException`
* Added new exception method `unknownPaymentMethod` for `Cicada\Core\Checkout\Customer\CustomerException`
___
# Storefront
* Changed `Cicada\Storefront\Controller\AccountPaymentController::savePayment` to catch `PaymentException` and forward to payment page with `success = false`.

