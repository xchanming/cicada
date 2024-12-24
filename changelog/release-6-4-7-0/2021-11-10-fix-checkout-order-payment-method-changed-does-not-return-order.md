---
title: Fix Checkout Order Payment method changed does not return order
issue: NEXT-18316
---
# Core
* Changed `OrderPaymentMethodChangedEvent` to implement `OrderAware`
* Added `Cicada\Core\Checkout\Order\Event\OrderPaymentMethodChangedCriteriaEvent`
* Changed `\Cicada\Core\Checkout\Order\SalesChannel\SetPaymentOrderRoute::loadOrder` method to dispatch `OrderPaymentMethodChangedCriteriaEvent` before fetching data
