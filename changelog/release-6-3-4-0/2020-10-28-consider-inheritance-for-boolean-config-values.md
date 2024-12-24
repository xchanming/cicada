---
title: Consider inheritance for boolean config values
issue: NEXT-9639
author: Philip Gatzka
author_email: p.gatzka@cicada.com 
author_github: @philipgatzka
---
# Core
* Changed `\Cicada\Core\System\SystemConfig\SystemConfigService::getDomain` method so it does not consider the boolean
  value `false` empty anymore.
