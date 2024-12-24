---
title: Fix removal of json-encoded app metadata during updates
issue: NEXT-13989
---
# Core
* Changed `\Cicada\Core\Framework\App\Lifecycle\AppLifecycle::update()` to remove existing modules and cookies metadata, if they are not present in manifest files any more. 
* Removed nullability of `\Cicada\Core\Framework\App\AppEntity::$cookies`
