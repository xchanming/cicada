---
title: re-evaluate rules in order context
issue: NEXT-18066
---
# Core
* Changed the `getContext` function in `Cicada\Core\Checkout\Order\Listener\OrderStateChangeEventListener` to rebuild the order context.
* Changed the functions to use the `restoreByCustomer` function to restore the customer context in:
  `accept` and `decline` functions in `Cicada\Core\Checkout\Customer\Api\CustomerGroupRegistrationActionController`
  `dispatchCustomerRegisterEvent` and `dispatchCustomerChangePaymentMethodEvent` functions in `Cicada\Core\Checkout\Customer\Subscriber\CustomerFlowEventsSubscriber`
* Changed the functions to use the `CartRestore::restore` instead `SalesChannelContextRestorer::restore` in:
  `loginByCustomer` function in `Cicada\Core\Checkout\Customer\SalesChannel\AccountService`
  `login` function in `Cicada\Core\Checkout\Customer\SalesChannel\LoginRoute`
* Added the `restoreByOrder` function in `Cicada\Core\System\SalesChannel\Context\SalesChannelContextRestorer` to restore the SaleChanelContext by order id.
* Added the `restoreByCustomer` function in `Cicada\Core\System\SalesChannel\Context\SalesChannelContextRestorer` to replace the SaleChanelContext by customer id.
* Added the `CartRestore` class in `Cicada\Core\System\SalesChannel\Context\CartRestore` to replace the `restore` function in `Cicada\Core\System\SalesChannel\Context\SalesChannelContextRestorer`
* Deprecated the `restore` function, use `CartRestore::restore` function instead.
