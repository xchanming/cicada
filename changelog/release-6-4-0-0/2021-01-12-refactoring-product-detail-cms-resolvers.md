---
title: Refactoring Product Detail CMS Resolvers
issue: NEXT-12990
---
# Core
* Added `Cicada\Core\Content\Cms\SalesChannel\Struct\ManufacturerLogoStruct` to handle data for `manufacturer-logo` cms element.
* Added a new abstract `Cicada\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver` to provide a common `collect` method for Product detail cms elements.
* Added `SalesChannelCmsPageLoaderInterface` and `SalesChannelProductDefinition` as dependencies for `Cicada\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute` to load CMS Page for a product entity if .
___
# Storefront
* Changed method `Cicada\Storefront\Page\Product\ProductPageLoader::load` to load cmsPage from ProductDetailRoute and do not load reviews and cross-sellings in this loader.
