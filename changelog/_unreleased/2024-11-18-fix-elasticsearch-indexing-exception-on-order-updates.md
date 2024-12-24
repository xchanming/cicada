---
title: Fix ElasticSearch indexing exception on order updates
issue: NEXT-39651
---
# Core
* Changed `Cicada\Core\Content\Product\Stock\StockStorage` so that `Cicada\Core\Content\Product\Events\ProductStockAlteredEvent` is not dispatched when no changes happened.
