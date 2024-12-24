---
title: Add app lifecycle scripts
issue: NEXT-19855
---
# Core
* Added AppLifecycleHooks in `\Cicada\Core\Framework\App\Event\Hooks`.
* Changed `\Cicada\Core\Framework\App\Lifecycle\AppLifecycle` and `\Cicada\Core\Framework\App\AppStateService` to execute the new app lifecycle hooks.
* Added `\Cicada\Core\Framework\Script\Execution\Awareness\AppSpecificHook` to mark hooks that should be only executed for specific apps.
* Changed `\Cicada\Core\Framework\Script\Execution\ScriptExecutor` to only execute scripts of a specific app for `AppSpecificHooks`. 
