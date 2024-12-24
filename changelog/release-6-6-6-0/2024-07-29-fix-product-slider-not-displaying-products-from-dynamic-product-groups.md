---
title: Fix product slider not displaying products from dynamic product groups
issue: NEXT-36102
author: Lukas Rump
---
# Core
* Changed `Cicada\Core\Content\Product\Cms\ProductSliderCmsElementResolver::handleProductStream` to add products with empty variant config.
