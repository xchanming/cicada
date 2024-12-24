---
title: Collect cache invalidations in Redis
issue: NEXT-30262
---

# Core

* Added `\Cicada\Core\Framework\Adapter\Cache\InvalidatorStorage\RedisInvalidatorStorage` to collect invalidations in Redis in a atomic operation.
* Deprecated `\Cicada\Core\Framework\Adapter\Cache\InvalidatorStorage\CacheInvalidatorStorage` and will be removed in 6.6

___
# Upgrade Information

## Deprecation of CacheInvalidatorStorage

We deprecated the default delayed cache invalidation storage, as it is not ideal for multi-server usage.
Make sure you switch until 6.6 to the new RedisInvalidatorStorage.

```yaml
cicada:
    cache:
        invalidation:
            delay_options:
                storage: cache
                dsn: 'redis://localhost'
```

___

# Next Major Version Changes

## Removal of CacheInvalidatorStorage

The delayed cache invalidation storage was until 6.6 the cache implementation.
As this is not ideal for multi-server usage, we deprecated it in 6.5 and removed it now.
Delaying of cache invalidations now requires a Redis instance to be configured.

```yaml
cicada:
    cache:
        invalidation:
            delay_options:
                storage: cache
                dsn: 'redis://localhost'
```
