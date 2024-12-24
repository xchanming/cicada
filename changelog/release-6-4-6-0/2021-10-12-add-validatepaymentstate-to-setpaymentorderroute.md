---
title: Add validatePaymentState to SetPaymentOrderRoute
issue: NEXT-16231
author: Sebastian Seggewiss
author_email: s.seggewiss@cicada.com 
author_github: seggewiss
---
# Core
* Added `\Cicada\Core\Checkout\Order\Exception\PaymentMethodNotChangeableException`
___
# API
* Changed `/store-api/order/payment` to validate if the transaction status allows for a payment method change
