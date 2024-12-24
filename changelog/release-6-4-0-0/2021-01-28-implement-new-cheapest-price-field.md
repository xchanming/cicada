---
title: Implement new cheapest price field
issue: NEXT-12169
author: OliverSkroblin
author_email: o.skroblin@cicada.com 
author_github: OliverSkroblin
---
# Core
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice`
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice`
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceAccessorBuilder`
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceContainer`
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPriceField`
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\AbstractCheapestPriceQuantitySelector`
* Added `\Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPriceUpdater` 
* Added `\Cicada\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection::filterByRuleId` 
* Added `\Cicada\Core\Content\Product\Aggregate\ProductPrice\ProductPriceCollection::sortByQuantity` 
* Added `\Cicada\Core\Content\Product\SalesChannel\Price\AbstractProductPriceCalculator` 
* Added `\Cicada\Core\Content\Product\SalesChannel\Price\ReferencePriceDto` 
* Added `\Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity::$calculatedCheapestPrice`
* Added `\Cicada\Core\Content\Product\ProductEntity::$cheapestPrice`, which contains the cheapest available price
* Added `\Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\PHPUnserializeFieldSerializer`, which can be used for php serialized values
* Added `\Cicada\Core\Framework\DataAbstractionLayer\VersionManager::DISABLE_AUDIT_LOG`, which allows to disable the audit log be written
* Added `\Cicada\Core\System\SalesChannel\SalesChannelContext::getCurrencyId` 
* Deprecated `\Cicada\Core\Content\Product\DataAbstractionLayer\Indexing\ListingPriceUpdater`, will be removed
* Deprecated `\Cicada\Core\Content\Product\SalesChannel\Price\ProductPriceDefinitionBuilderInterface`, use `AbstractProductPriceCalculator` instead
* Deprecated `\Cicada\Core\Content\Product\SalesChannel\Price\ProductPriceDefinitions`, will be removed
* Deprecated `\Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity::$calculatedListingPrice`, use `calculatedCheapestPrice` instead
* Deprecated `\Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity::getCalculatedListingPrice`, use `calculatedCheapestPrice` instead
* Deprecated `\Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity::setCalculatedListingPrice`, use `calculatedCheapestPrice` instead
* Deprecated `\Cicada\Core\Content\Product\ProductEntity::$grouped`, will be removed
* Deprecated `\Cicada\Core\Content\Product\ProductEntity::setGrouped`, will be removed
* Deprecated `\Cicada\Core\Content\Product\ProductEntity::isGrouped`, will be removed
* Deprecated `\Cicada\Core\Content\Product\ProductEntity::$listingPrices`, use `cheapestPrice` instead
* Deprecated `\Cicada\Core\Content\Product\ProductEntity::getListingPrices`, use `cheapestPrice` instead
* Deprecated `\Cicada\Core\Content\Product\ProductEntity::setListingPrices`, use `cheapestPrice` instead
