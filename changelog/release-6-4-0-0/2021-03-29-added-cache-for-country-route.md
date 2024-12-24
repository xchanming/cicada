---
title: Added cache for country route
issue: NEXT-14094
author: OliverSkroblin
author_email: o.skroblin@cicada.com 
author_github: OliverSkroblin
---
# Core
* Added `\Cicada\Core\System\Country\SalesChannel\CachedCountryRoute`, which adds a cache for the store api country route
* Added `\Cicada\Core\System\Salutation\SalesChannel\CachedSalutationRoute`, which adds a cache for the store api salutation route
* Added `Request $request` parameter to `\Cicada\Core\System\Country\SalesChannel\AbstractCountryRoute::load`
* Added `\Cicada\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute`, which adds a cache for the store api product review route
* Added `\Cicada\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute`, which adds a cache for the store api sitemap route
* Added `\Cicada\Core\Content\Sitemap\Event\SitemapGeneratedEvent`, which is dispatched when a sitemap was generated
