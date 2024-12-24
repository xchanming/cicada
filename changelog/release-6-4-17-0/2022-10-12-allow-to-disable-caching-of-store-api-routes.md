---
title: Allow to disable caching of store-api-routes
issue: NEXT-23648
author: Simon Vorgers & Viktor Buzyka
author_email: s.vorgers@cicada.com
author_github: SimonVorgers
---
# Core
* Changed `Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent` to allow to disable caching of store-api-routes
* Changed following Cached-Store-API-Routes to implement the new logic:
  * `Cicada\Core\Checkout\Payment\SalesChannel\CachedPaymentMethodRoute` 
  * `Cicada\Core\Checkout\Shipping\SalesChannel\CachedShippingMethodRoute` 
  * `Cicada\Core\Content\Category\SalesChannel\CachedCategoryRoute` 
  * `Cicada\Core\Content\Category\SalesChannel\CachedNavigationRoute` 
  * `Cicada\Core\Content\LandingPage\SalesChannel\CachedLandingPageRoute` 
  * `Cicada\Core\Content\Product\SalesChannel\CrossSelling\CachedProductCrossSellingRoute` 
  * `Cicada\Core\Content\Product\SalesChannel\Detail\CachedProductDetailRoute` 
  * `Cicada\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute` 
  * `Cicada\Core\Content\Product\SalesChannel\Search\CachedProductSearchRoute` 
  * `Cicada\Core\Content\Product\SalesChannel\Suggest\CachedProductSuggestRoute` 
  * `Cicada\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute` 
  * `Cicada\Core\System\Country\SalesChannel\CachedCountryRoute` 
  * `Cicada\Core\System\Country\SalesChannel\CachedCountryStateRoute` 
  * `Cicada\Core\System\Currency\SalesChannel\CachedCurrencyRoute` 
  * `Cicada\Core\System\Language\SalesChannel\CachedLanguageRoute` 
  * `Cicada\Core\System\Salutation\SalesChannel\CachedSalutationRoute` 
___
# Upgrade Information
## Disabling caching of store-api-routes
The Cache for Store-API-Routes can now be disabled by implementing the `Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent` and calling `disableCache()` method on the event.
