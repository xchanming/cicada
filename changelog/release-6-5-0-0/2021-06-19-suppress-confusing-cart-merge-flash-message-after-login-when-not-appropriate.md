---
title: Suppress confusing cart merge flash message after login when not appropriate
issue: NEXT-16482
author: Axel Guckelsberger
author_email: axel.guckelsberger@guite.de
---
# Core
* Changed constructor of `Cicada\Core\Checkout\Cart\Event\CartMergedEvent` to accept a previous cart.
* Added method `Cicada\Core\Checkout\Cart\Event\CartMergedEvent::getPreviousCart()`.
* Changed method `Cicada\Core\System\SalesChannel\Context\SalesChannelContextRestorer::mergeCart()` to provide the `CartMergedEvent` with the previous cart and not clone errors
