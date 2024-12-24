---
title: Fixed the display of max purchase quantity with purchase steps greater than one
issue: NEXT-14493
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com 
author_github: @lernhart
---
# Core
* Changed `Cicada\Core\Content\Product\SalesChannel\SalesChannelProductSubscriber` to now correctly return the max purchasable quantity when a purchase step greater than `1` is given.
