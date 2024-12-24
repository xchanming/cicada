---
title: Add payment method handler runtime fields
issue: NEXT-17157
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com 
author_github: @lernhart
---
# Core
* Added `synchronous` runtime field to `Cicada\Core\Checkout\Payment\PaymentMethodDefinition`.
* Added `synchronous` property to `Cicada\Core\Checkout\Payment\PaymentMethodEntity`.
* Added `asynchronous` runtime field to `Cicada\Core\Checkout\Payment\PaymentMethodDefinition`.
* Added `asynchronous` property to `Cicada\Core\Checkout\Payment\PaymentMethodEntity`.
* Added `prepared` runtime field to `Cicada\Core\Checkout\Payment\PaymentMethodDefinition`.
* Added `prepared` property to `Cicada\Core\Checkout\Payment\PaymentMethodEntity`.
* Changed `Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentHandlerIdentifierSubscriber` to update the runtime fields whenever the payment handler inherits the corresponding interface. 
