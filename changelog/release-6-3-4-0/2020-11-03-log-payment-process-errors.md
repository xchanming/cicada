---
title: Log payment process errors
issue: NEXT-11807
author: Michael Telgmann
---
# Core
*  Added logging of errors during the payment process in `Cicada\Core\Checkout\Payment\PaymentService::handlePaymentByOrder` and `Cicada\Core\Checkout\Payment\PaymentService::finalizeTransaction`
