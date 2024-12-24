---
title: Promotions Rework deprecations
issue: NEXT-12016
---
# Core
* Added `Cicada\Core\Checkout\Promotion\Util\PromotionCodeService` and `Cicada\Core\Checkout\Promotion\Api\PromotionController`
* Added Exceptions in `Cicada\Core\Checkout\Promotion\Exception`:
  * `PatternNotComplexEnoughException`
  * `PatternAlreadyInUseException`
* Deprecated `Cicada\Core\Checkout\Promotion\Util\PromotionCodesLoader` and `Cicada\Core\Checkout\Promotion\Util\PromotionCodesRemover` for tag:v6.4.0.0. Use the EntityRepository or PromotionCodeService instead.
___
# API
* Deprecated `Cicada\Core\Checkout\Promotion\Api\PromotionActionController` for tag:v6.4.0.0. Use the PromotionController instead.
