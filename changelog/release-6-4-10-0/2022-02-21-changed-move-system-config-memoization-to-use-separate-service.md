---
title: Changed system config memoization to use separate service.
issue: https://github.com/cicada/platform/issues/2319
author: Andreas Allacher
author_email: andreas.allacher@massiveart.com
author_github: @AndreasA
---
# Core
* Changed `Cicada\Core\System\SystemConfig\SystemConfigService` to not memoize the system configuration.
* Added `Cicada\Core\System\SystemConfig\Store\MemoizedSystemConfigStore` to memoize the system configuration and clear it upon changes.
* Added `Cicada\Core\System\SystemConfig\MemoizedSystemConfigLoader` to memoize the system configuration in `Cicada\Core\System\SystemConfig\Store\MemoizedSystemConfigStore`.
