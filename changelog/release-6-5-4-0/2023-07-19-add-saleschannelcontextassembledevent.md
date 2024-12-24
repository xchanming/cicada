---
title: Add SalesChannelContextAssembledEvent
issue: NEXT-26449
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com
author_github: @lernhart
---
# Core
* Added `Cicada\Core\Checkout\Cart\Event\SalesChannelContextAssembledEvent`,  which is dispatched, whenever a sales channel context is created via an order and allows manipulation of the context afterwards.
