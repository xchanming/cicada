---
title: Consider key of CachedBaseContextFactory to invalidate context cache
issue: NEXT-24759
author: Frederik Schmitt
author_email: f.schmitt@cicada.com
author_github: fschmtt
---
# Core
* Changed `Cicada\Core\Framework\Adapter\Cache\CacheInvalidationSubscriber::invalidateContext()` to consider the key of `Cicada\Core\Framework\Adapter\Cache\CachedBaseContextFactory` to invalidate the context cache.
