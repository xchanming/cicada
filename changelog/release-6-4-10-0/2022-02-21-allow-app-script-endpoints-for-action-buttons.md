---
title: Allow AppScript endpoints as valid target URL for ActionButtons
issue: NEXT-20202
---
# Core
* Changed `\Cicada\Core\Framework\App\Lifecycle\AppLifecycle` to persist ActionButtons even if no setup section is provided in manifest.xml.
* Changed `\Cicada\Core\Framework\App\ActionButton\AppAction` to allow null as AppSecret and relative target URLs.
* Changed `\Cicada\Core\Framework\App\ActionButton\Executor` to execute sub-requests if target URL of AppAction is relative.
