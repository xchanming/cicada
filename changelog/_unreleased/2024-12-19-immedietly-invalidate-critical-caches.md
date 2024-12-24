---
title: Immediately invalidate critical caches
issue: NEXT-40112
---
# Core
* Changed `\Cicada\Core\Framework\Adapter\Cache\CacheInvalidationSubscriber` to force the immediate invalidation of the following caches:
  * `\Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader`
  * `\Cicada\Core\System\SystemConfig\CachedSystemConfigLoader`
  * `\Cicada\Core\Checkout\Cart\CachedRuleLoader`
  * `\Cicada\Core\System\SalesChannel\Context\CachedSalesChannelContextFactory`
  * `\Cicada\Core\System\SalesChannel\Context\CachedBaseContextFactory`
