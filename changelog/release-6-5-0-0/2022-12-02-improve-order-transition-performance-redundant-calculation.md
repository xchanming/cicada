---
title: Improve order transition performance by removing redundant calculation
issue: NEXT-24671
author: Joshua Behrens
author_email: code@joshua-behrens.de
author_github: @JoshuaBehrens
---
# Core
* Changed `\Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdater::updateStock` to also update sales in inverse direction and renamed it to `\Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdater::updateStockAndSales` 
* Removed and replaced calls to `\Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdater::increaseStock`/`decreaseStock` to just call `::updateStockAndSales` on order state transition to skip heavy calculations within `\Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdater::updateAvailableStockAndSales`, that recalculates indexed values, that are already on their correct value at that time 
