---
title: Changed `PromotionGatewayInterface` return type to `PromotionCollection`
issue: NEXT-38798
author: Max
author_email: max@swk-web.com
author_github: @aragon999
---
# Core
* Changed the return type of the `Cicada\Core\Checkout\Promotion\Gateway\PromotionGatewayInterface` from `Cicada\Core\Framework\DataAbstractionLayer\EntityCollection<PromotionEntity>` to `Cicada\Core\Checkout\Promotion\PromotionCollection`, which will be adjusted in the next major Cicada version
* Changed the return type of the `Cicada\Core\Checkout\Promotion\Gateway\PromotionGateway` from `Cicada\Core\Framework\DataAbstractionLayer\EntityCollection<PromotionEntity>` to `Cicada\Core\Checkout\Promotion\PromotionCollection`, which will be adjusted in the next major Cicada version
* Changed some internals of the `Cicada\Core\Checkout\Promotion\Cart\PromotionCollector`
* Deprecated the return type of `Cicada\Core\Checkout\Promotion\Gateway\PromotionGatewayInterface` to change from `EntityCollection<PromotionEntity>` to `PromotionCollection`
___
# Next Major Version Changes
## Changed PromotionGatewayInterface
* Changed the return type of the `Cicada\Core\Checkout\Promotion\Gateway\PromotionGatewayInterface` from `EntityCollection<PromotionEntity>` to `PromotionCollection`
