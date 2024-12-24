---
title: Introduce a new interface for events containing the SalesChannelContext 
issue: NEXT-11926
author: Michael Telgmann
---
# Core
* Added new interface `Cicada\Core\Framework\Event\CicadaSalesChannelEvent`. Use it to indicate that your event contains the `Cicada\Core\System\SalesChannel\SalesChannelContext`
* Added `Cicada\Core\Framework\Event\CicadaSalesChannelEvent` interface to the following event classes
  * `Cicada\Core\Checkout\Cart\Order\CartConvertedEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerAccountRecoverRequestEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerBeforeLoginEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerChangedPaymentMethodEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerDeletedEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerLoginEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerLogoutEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerRegisterEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerWishlistLoaderCriteriaEvent`
  * `Cicada\Core\Checkout\Customer\Event\CustomerWishlistProductListingResultEvent`
  * `Cicada\Core\Content\Category\Event\NavigationLoadedEvent`
  * `Cicada\Core\Content\Cms\Events\CmsPageLoadedEvent`
  * `Cicada\Core\Content\Cms\Events\CmsPageLoaderCriteriaEvent`
  * `Cicada\Core\Content\Product\Events\ProductCrossSellingCriteriaEvent`
  * `Cicada\Core\Content\Product\Events\ProductCrossSellingsLoadedEvent`
  * `Cicada\Core\Content\Product\Events\ProductListingCollectFilterEvent`
  * `Cicada\Core\Content\Product\Events\ProductListingCriteriaEvent`
  * `Cicada\Core\Content\Product\Events\ProductListingResultEvent`
  * `Cicada\Core\Framework\Routing\Event\SalesChannelContextResolvedEvent`
  * `Cicada\Core\System\SalesChannel\Entity\SalesChannelEntityAggregationResultLoadedEvent`
  * `Cicada\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent`
  * `Cicada\Core\System\SalesChannel\Entity\SalesChannelEntityLoadedEvent`
  * `Cicada\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent`
  * `Cicada\Core\System\SalesChannel\Event\SalesChannelContextPermissionsChangedEvent`
  * `Cicada\Core\System\SalesChannel\Event\SalesChannelContextSwitchEvent`
  * `Cicada\Core\System\SalesChannel\Event\SalesChannelContextTokenChangeEvent`
* Deprecated following classes which will implement the `Cicada\Core\Framework\Event\CicadaSalesChannelEvent` interface with Cicada 6.4.0.0
  * `Cicada\Core\Checkout\Cart\Event\CartDeletedEvent`
  * `Cicada\Core\Checkout\Cart\Event\CartMergedEvent`
  * `Cicada\Core\Checkout\Cart\Event\CartSavedEvent`
  * `Cicada\Core\Checkout\Cart\Event\LineItemAddedEvent`
  * `Cicada\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent`
  * `Cicada\Core\Checkout\Cart\Event\LineItemRemovedEvent`
* Deprecated following methods which will return `Cicada\Core\Framework\Context` with Cicada 6.4.0.0. Use `getSalesChannelContext()` to get the `Cicada\Core\System\SalesChannel\SalesChannelContext` instead.
  * `Cicada\Core\Checkout\Cart\Event\CartDeletedEvent::getContext()`
  * `Cicada\Core\Checkout\Cart\Event\CartMergedEvent::getContext()`
  * `Cicada\Core\Checkout\Cart\Event\CartSavedEvent::getContext()`
  * `Cicada\Core\Checkout\Cart\Event\LineItemAddedEvent::getContext()`
  * `Cicada\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent::getContext()`
  * `Cicada\Core\Checkout\Cart\Event\LineItemRemovedEvent::getContext()`
___
# Storefront
* Added `Cicada\Core\Framework\Event\CicadaSalesChannelEvent` interface to the following event classes
  * `Cicada\Storefront\Event\StorefrontRenderEvent`
  * `Cicada\Storefront\Event\RouteRequest\RouteRequestEvent`
  * `Cicada\Storefront\Page\PageLoadedEvent`
  * `Cicada\Storefront\Page\Address\Listing\AddressListingCriteriaEvent`
  * `Cicada\Storefront\Page\Product\ProductLoaderCriteriaEvent`
  * `Cicada\Storefront\Page\Product\CrossSelling\CrossSellingLoadedEvent`
  * `Cicada\Storefront\Page\Product\CrossSelling\CrossSellingProductCriteriaEvent`
  * `Cicada\Storefront\Page\Product\Review\ProductReviewsLoadedEvent`
  * `Cicada\Storefront\Pagelet\PageletLoadedEvent`
* Deprecated following classes which will implement the `Cicada\Core\Framework\Event\CicadaSalesChannelEvent` interface with Cicada 6.4.0.0
  * `Cicada\Storefront\Page\Product\QuickView\MinimalQuickViewPageCriteriaEvent`
  * `Cicada\Storefront\Page\Product\ProductPageCriteriaEvent`
* Deprecated following methods which will return the `Cicada\Core\Framework\Context` with Cicada 6.4.0.0. Use `getSalesChannelContext()` to get the `Cicada\Core\System\SalesChannel\SalesChannelContext` instead.
  * `Cicada\Storefront\Page\Product\QuickView\MinimalQuickViewPageCriteriaEvent::getContext()`
  * `Cicada\Storefront\Page\Product\ProductPageCriteriaEvent::getContext()`
