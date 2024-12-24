---
title: Remove deprecated autoload === true associations
issue: NEXT-25333
---
# Core
* Changed `Cicada\Core\Checkout\Shipping\ShippingMethodDefinition` to remove deprecated autoload === true for properties:
  * `deliveryTime`
  * `appShippingMethod`
* Changed `Cicada\Core\Checkout\Payment\PaymentMethodDefinition` to remove deprecated autoload === true for `appPaymentMethod`.
* Changed `Cicada\Core\System\NumberRange\NumberRangeDefinition` to remove deprecated autoload === true for `state`.
* Changed `Cicada\Core\Content\Rule\Aggregate\RuleCondition\RuleConditionDefinition` to remove deprecated autoload === true for `appScriptCondition`.
* Changed `Cicada\Core\Content\Product\ProductDefinition` to remove deprecated autoload === true for `tax`.
* Changed `Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition` to remove deprecated autoload === true for `media`.
