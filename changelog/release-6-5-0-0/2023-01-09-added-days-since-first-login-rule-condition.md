---
title: Added days since first login rule condition
issue: NEXT-21041
author: d.neustadt
author_email: d.neustadt@cicada.com
author_github: dneustadt
---
# Core
* Added `Cicada\Core\Checkout\Customer\Rule\DaysSinceFirstLoginRule`
* Deprecated `Cicada\Core\Checkout\Customer\Rule\IsNewCustomerRule`
___
# Next Major Version Changes
## `IsNewCustomerRule` to be removed with major release v6.6.0
* Use `DaysSinceFirstLoginRule` instead with operator `=` and `daysPassed` of `0` to achieve identical behavior
