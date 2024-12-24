---
title: Fix reset of config values on updating an deactivated plugin
issue: NEXT-20398
author: Max Stegmeyer
author_email: m.stegmeyer@cicada.com
---
# Core
* Changed `Cicada\Core\System\SystemConfig\SystemConfigService` to correctly obtain existing deactivated plugin config values
