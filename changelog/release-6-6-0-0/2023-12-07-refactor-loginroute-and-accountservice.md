---
title: Refactor LoginRoute and AccountService
issue: NEXT-32258
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Added method `Cicada\Core\Checkout\Customer\SalesChannel\AccountService::loginByCredentials`
* Changed `Cicada\Core\Checkout\Customer\SalesChannel\LoginRoute` and moved login logic into the `AccountService`
* Deprecated method `Cicada\Core\Checkout\Customer\SalesChannel\AccountService::login` use `AccountService::loginByCredentials` or `AccountService::loginById` instead
* Deprecated unused constant `Cicada\Core\Checkout\Customer\CustomerException::CUSTOMER_IS_INACTIVE` and unused method `Cicada\Core\Checkout\Customer\CustomerException::inactiveCustomer`
___
# Upgrade Information
## Cicada\Core\Checkout\Customer\SalesChannel\AccountService::login is removed

The `Cicada\Core\Checkout\Customer\SalesChannel\AccountService::login` method will be removed in the next major version. Use `AccountService::loginByCredentials` or `AccountService::loginById` instead.
___
# Next Major Version Changes
## AccountService refactoring

The `Cicada\Core\Checkout\Customer\SalesChannel\AccountService::login` method is removed. Use `AccountService::loginByCredentials` or `AccountService::loginById` instead.

Unused constant `Cicada\Core\Checkout\Customer\CustomerException::CUSTOMER_IS_INACTIVE` and unused method `Cicada\Core\Checkout\Customer\CustomerException::inactiveCustomer` are removed.
