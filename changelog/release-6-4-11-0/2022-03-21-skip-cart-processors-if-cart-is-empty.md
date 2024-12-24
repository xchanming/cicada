---
title: Skip cart processors & collectors if cart is empty
issue: NEXT-20690
---
# Core
* Changed `\Cicada\Core\Checkout\Cart\Processor::process()` to skip CartProcessors and collectors if the cart is empty beginning with v6.5.0.0.
___
# Next Major Version Changes
## Skipping of the cart calculation if the cart is empty

If the cart is empty the cart calculation will be skipped.
This means that all `\Cicada\Core\Checkout\Cart\CartProcessorInterface` and `\Cicada\Core\Checkout\Cart\CartDataCollectorInterface` will not be executed anymore if the cart is empty.
