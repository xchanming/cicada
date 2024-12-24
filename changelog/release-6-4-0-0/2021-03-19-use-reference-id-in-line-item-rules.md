---
title: Use reference id in line item rules
issue: NEXT-13475
author: OliverSkroblin
author_email: o.skroblin@cicada.com 
author_github: OliverSkroblin
---
# Core
* Changed `\Cicada\Core\Checkout\Cart\Rule\LineItemWithQuantityRule` to use the `\Cicada\Core\Checkout\Cart\LineItem\LineItem::$referencedId` instead of the `\Cicada\Core\Checkout\Cart\LineItem\LineItem::$id`
