---
title: Preparation that shipping methods no longer require an availability rule to be valid as of version 6.6.0.0
issue: NEXT-30858
---
# Core
* Deprecated `availabilityRuleId` in `\Cicada\Core\Checkout\Shipping\ShippingMethodEntity` because type can be nullable in v6.6.0. Also, it will be natively typed to enforce strict data type checking.
* Deprecated `\Cicada\Core\Checkout\Shipping\ShippingMethodEntity::getAvailabilityRuleId` because return type can be nullable in 6.6.0.0.
* Added `null` as a possible parameter for `\Cicada\Core\Checkout\Shipping\ShippingMethodEntity::setAvailabilityRuleId`.
* Changed the following methods so that shipping methods without availability rule will always evaluate as valid in 6.6.0.0:
  * `\Cicada\Core\Checkout\Shipping\ShippingMethodCollection::filterByActiveRules`
  * `\Cicada\Core\Checkout\Cart\Delivery\DeliveryValidator::validate`
* Deprecated `Required` flag for `availability_rule_id` in `\Cicada\Core\Checkout\Shipping\ShippingMethodDefinition`.
* Deprecated `\Cicada\Core\Framework\App\Lifecycle\Persister\ShippingMethodPersister::getAvailabilityRuleUuid` without replacement.
* Added a new Migration for 6.6.0.0 `\Cicada\Core\Migration\V6_6\Migration1697788982ChangeColumnAvailabilityRuleIdFromShippingMethodToNullable` so that the `availability_rule_id` column in the `shipping_method` table is `NULL` by default.
___
# Next Major Version Changes
## `availabilityRuleId` in `\Cicada\Core\Checkout\Shipping\ShippingMethodEntity`:
* Type changed from `string` to be also nullable and will be natively typed to enforce strict data type checking.
## `getAvailabilityRuleId` in `\Cicada\Core\Checkout\Shipping\ShippingMethodEntity`:
* Return type is nullable.
## `getAvailabilityRuleUuid` in `\Cicada\Core\Framework\App\Lifecycle\Persister\ShippingMethodPersister`:
* Has been removed without replacement.
## `Required` flag for `availability_rule_id` in `\Cicada\Core\Checkout\Shipping\ShippingMethodDefinition`:
* Has been removed.
