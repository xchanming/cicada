---
title: Hide configurable custom product default layout
issue: NEXT-24968
flag: v6.6.0.0
author: Niklas Limberg
author_email: n.limberg@cicada.com
author_github: Niklas Limberg
---
# Core
* Changed `Cicada\Core\Content\Product\Subscriber\ProductSubscriber` to restore the behavior before the removal of the 6.5 feature flag.
___
# Storefront
* Changed `Cicada\Storefront\Controller\ProductController` and `Cicada\Storefront\Page\Product\ProductPageLoader` to restore the behavior before the removal of the 6.5 feature flag.
* Deprecated template file `Resources/views/storefront/page/product-detail/index.html.twig`. Will be removed and replaced by configurable product detail cms pages. Use `@Storefront/storefront/page/content/product-detail.html.twig` instead.
