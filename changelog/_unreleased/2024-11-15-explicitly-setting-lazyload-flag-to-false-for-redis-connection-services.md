---
title: Explicitly setting lazyLoad flag to false for redis connection services
issue: NEXT-39627
---
# Core
* Changed `\Cicada\Core\Framework\Adapter\Redis\RedisConnectionsCompilerPass` to explicitly set the `lazyLoad` flag to `false` (current default value) for all services that are tagged with `cicada.redis.connection`.
