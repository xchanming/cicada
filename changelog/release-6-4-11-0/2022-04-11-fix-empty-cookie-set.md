---
title: Fix empty cookie set
issue: NEXT-20683
---

# Storefront
* Changed `\Cicada\Storefront\Framework\Cache\CacheResponseSubscriber` to delete only the cookie when the client also has the cookie


