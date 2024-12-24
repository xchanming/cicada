---
title: Remove core dependencies from CartLineItemController
issue: NEXT-21967
author: Stefan Sluiter
author_email: s.sluiter@cicada.com
author_github: ssltg
---
# Storefront
* Removed `Cicada\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface` as a constructor argument from `Cicada\Storefront\Controller\CartLineItemController`
* Changed `Cicada\Storefront\Controller\CartLineItemController` to use `Cicada\Core\Content\Product\SalesChannel\AbstractProductListRoute`
