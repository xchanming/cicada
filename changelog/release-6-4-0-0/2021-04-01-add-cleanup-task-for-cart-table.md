---
title: Add cleanup task for cart table
issue: NEXT-14155
author: OliverSkroblin
author_email: o.skroblin@cicada.com 
author_github: OliverSkroblin
---
# Core
* Added `\Cicada\Core\Checkout\Cart\Cleanup\CleanupCartTaskHandler`, which delete all carts which are older than 120(`cicada.cart.expire_days`) days
* Added `cicada.cart.expire_days` config to define expire time for cart table entries
