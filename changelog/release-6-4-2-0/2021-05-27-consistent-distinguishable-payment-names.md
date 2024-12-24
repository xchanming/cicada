---
title: Consistently generate distinguishable names
issue: NEXT-15331
---
# Core
* Added `Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentDistinguishableNameGenerator` to generate distinguishable names
* Changed `Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentDistinguishableNameSubscriber` to only adding distinguishable names as fallback
* Added `Cicada\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodIndexer`
* Added `Cicada\Core\Checkout\Payment\Event\PaymentMethodIndexerEvent`
* Changed `Cicada\Core\Migration\V6_4\Migration1620733405DistinguishablePaymentMethodName` to trigger new `PaymentMethodIndexer`
