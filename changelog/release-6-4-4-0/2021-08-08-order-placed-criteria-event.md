---
title: Order Placed Criteria Event
issue: NEXT-16600
author: Konstantin Kiritsenko
author_email: k@componentk.com
author_github: @augsteyer
---
# Core
* Added event `Cicada\Core\Checkout\Cart\Event\CheckoutOrderPlacedCriteriaEvent`.
* Changed method `Cicada\Core\Checkout\Cart\SalesChannel\CartOrderRoute::order()` to fire `CheckoutOrderPlacedCriteriaEvent`.
