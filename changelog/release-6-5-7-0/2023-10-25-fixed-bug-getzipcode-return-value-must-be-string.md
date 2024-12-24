---
title: Fixed bug getZipcode return value must be string
issue: NEXT-31148
author: Florian Keller
author_email: f.keller@cicada.com
---
# Core
* Changed `Cicada\Core\Checkout\Order\Aggregate\OrderAddress::getZipcode()` to avoid nullable return value, string is expected.

