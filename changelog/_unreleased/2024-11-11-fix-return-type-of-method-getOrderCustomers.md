---
title: Fix return type of method getOrderCustomers
issue: NEXT-00000
author: Moritz Müller
author_email: moritz@momocode.de
author_github: @momocode-de
---
# Core
* Changed method `getOrderCustomers` in `Cicada\Core\Checkout\Order\OrderCollection` class to return a `Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerCollection` instead of `Cicada\Core\Checkout\Customer\CustomerCollection`
