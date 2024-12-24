---
title: Move product review loader to core
issue: NEXT-36468
author: Benjamin Wittwer
author_email: benjamin.wittwer@a-k-f.de
author_github: akf-bw
---
# Core
* Changed `\Cicada\Core\Content\Product\Cms\ProductDescriptionReviewsCmsElementResolver` to make use of the `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader` and removed the duplicate methods
* Changed `\Cicada\Core\Content\Product\Cms\ProductDescriptionReviewsCmsElementResolver` to now execute the `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook`
* Added `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader` to allow overwriting product review loading logic
* Added `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewLoader` based on the now deprecated `\Cicada\Storefront\Page\Product\Review\ProductReviewLoader`
* Changed `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult` to include the `totalReviewsInCurrentLanguage` field
* Added `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook` based on the now deprecated `\Cicada\Storefront\Page\Product\Review\ProductReviewsWidgetLoadedHook`
* Added `\Cicada\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent` based on the now deprecated `\Cicada\Storefront\Page\Product\Review\ProductReviewsLoadedEvent`
* Added `core.listing.reviewsPerPage` to config `listing` with default value `10`
___
# Storefront
* Changed `\Cicada\Storefront\Controller\CmsController` to use newly introduced `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader`
* Changed `\Cicada\Storefront\Controller\ProductController` to use newly introduced `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader`
* Deprecated `\Cicada\Storefront\Page\Product\Review\ProductReviewLoader`. Use `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader` instead
* Deprecated `\Cicada\Storefront\Page\Product\Review\ProductReviewsLoadedEvent`. Use `\Cicada\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent` instead
* Deprecated `\Cicada\Storefront\Page\Product\Review\ProductReviewsWidgetLoadedHook`. Use `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook` instead
* Deprecated `\Cicada\Storefront\Page\Product\Review\ReviewLoaderResult`. Use `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult` instead
* Changed `review.html.twig` template to include the new config `core.listing.reviewsPerPage`
* Changed `review.html.twig` template to include missing `totalReviewsInCurrentLanguage` and `numberOfReviewsNotInCurrentLanguage` variables
* Added new blocks `component_review_list_action_filters` and `component_review_list_counter` to `review.html.twig`
___
# Upgrade Information

## Product review loading moved to core
The logic responsible for loading product reviews was unified and moved to the core.
* The service `\Cicada\Storefront\Page\Product\Review\ProductReviewLoader` is deprecated. Use `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader` instead.
* The event `\Cicada\Storefront\Page\Product\Review\ProductReviewsLoadedEvent` is deprecated. Use `\Cicada\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent` instead.
* The hook `\Cicada\Storefront\Page\Product\Review\ProductReviewsWidgetLoadedHook` is deprecated. Use `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook` instead.
* The struct `\Cicada\Storefront\Page\Product\Review\ReviewLoaderResult` is deprecated. Use `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult` instead.
___
# Next Major Version Changes

## Removal of deprecated product review loading logic in Storefront
* The service `\Cicada\Storefront\Page\Product\Review\ProductReviewLoader` was removed. Use `\Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewLoader` instead.
* The event `\Cicada\Storefront\Page\Product\Review\ProductReviewsLoadedEvent` was removed. Use `\Cicada\Core\Content\Product\SalesChannel\Review\Event\ProductReviewsLoadedEvent` instead.
* The hook `\Cicada\Storefront\Page\Product\Review\ProductReviewsWidgetLoadedHook` was removed. Use `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewsWidgetLoadedHook` instead.
* The struct `\Cicada\Storefront\Page\Product\Review\ReviewLoaderResult` was removed. Use `\Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult` instead.
