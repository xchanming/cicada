---
title: Reduced data loaded in Store-API Register Route and Register related events
issue: NEXT-39603
---
# Core

* Changed `\Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute` to trigger indexer asynchronously and use the BaseContextFactory cache
* Deprecated the default loaded associations in `\Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute` on following events the associations of CustomerEntity are not loaded anymore:

- `\Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent`
- `\Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent`
- `\Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent`
- `\Cicada\Core\Checkout\Customer\Event\DoubleOptInGuestOrderEvent`
- `\Cicada\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent`


___

# Next Major Version Changes

## Reduced data loaded in Store-API Register Route and Register related events

The customer entity does not have all associations loaded by default anymore. 
This change reduces the amount of data loaded in the Store-API Register Route and Register related events to improve the performance.

In the following event, the CustomerEntity has no association loaded anymore:

- `\Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent`
- `\Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent`
- `\Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent`
- `\Cicada\Core\Checkout\Customer\Event\DoubleOptInGuestOrderEvent`
- `\Cicada\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent`
