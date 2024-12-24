---
title: Don't consider user given limits in storefront
issue: NEXT-37585
---
# Core
* Changed the private function `getLimit` in `Cicada\Core\Content\Product\SalesChannel\Listing\Processor\PagingListingProcessor` to ignore the limit in request.
* Changed the private function `createReviewCriteria` in `Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewLoader` to ignore the limit in request.
___
# Storefront
* Changed the private function `createCriteria` in `Cicada\Storefront\Page\Account\Order\AccountOrderPageLoader` to ignore the limit in request.
* Changed the private function `createCriteria` in `Cicada\Storefront\Page\Wishlist\WishlistPageLoader` to ignore the limit in request.
