---
title: Add possibility to filter products handled by stock updater
issue: NEXT-23356
author_github: @Dominik28111
---
# Core
* Added abstract class `Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdate\AbstractProductStockUpdater`.
* Added class `Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdate\StockUpdateFilterProvider`.
* Changed method `Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdater::update()` to use `StockUpdateFilterHandler`.
