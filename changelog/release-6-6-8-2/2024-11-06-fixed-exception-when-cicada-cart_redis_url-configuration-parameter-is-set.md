---
title: Fixed exception when cicada.cart_redis_url configuration parameter is set
issue: NEXT-39439

---
# Core
* Changed `Cicada\Core\Checkout\DependencyInjection\CompilerPass\CartRedisCompilerPass` to fix an exception that was thrown when the `cicada.cart_redis_url` configuration parameter was set.

