---
title: Fixed bug with 0 price product discount
issue: NEXT-21158
author: Florian Keller
author_email: f.keller@cicada.com
---
# Core

* Changed \Cicada\Core\Checkout\Promotion\Cart\Discount\Calculator\DiscountAbsoluteCalculator to avoid division by zero when product price is 0. 
