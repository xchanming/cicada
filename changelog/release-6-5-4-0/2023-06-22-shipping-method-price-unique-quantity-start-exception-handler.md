---
title: Shipping method price unique quantity start exception handler
issue: NEXT-27480
author: Lennart Tinkloh
author_email: l.tinkloh@cicada.com
author_github: @lernhart
---
# Core
* Added `Cicada\Core\Checkout\Shipping\Aggregate\ShippingMethodPrice\ShippingMethodPriceExceptionHandler` to catch uniq key exceptions from the database and transform them to proper DAL exceptions.
```
