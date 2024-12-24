---
title: Not found cache
issue: NEXT-22404
author: Soner Sayakci
author_email: s.sayakci@cicada.com
---

# Storefront
* Added `\Cicada\Storefront\Framework\Routing\NotFound\NotFoundSubscriber` to handle 404 pages and cache the page.
  * `\Cicada\Storefront\Framework\Routing\NotFound\NotFoundPageCacheKeyEvent` can be used to manipulate the cache key
  * `\Cicada\Storefront\Framework\Routing\NotFound\NotFoundPageTagsEvent` can be used to manipulate the cache tags

