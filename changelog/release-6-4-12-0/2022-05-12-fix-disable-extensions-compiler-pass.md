---
title: Fix DisableExtensionsCompilerPass
issue: NEXT-21579
---
# Core
* Changed `\Cicada\Core\Framework\DependencyInjection\CompilerPass\DisableExtensionsCompilerPass` to correctly override `ActiveAppsLoader`-service if `DISABLE_EXTENSIONS` is set.
* Changed `\Cicada\Core\Framework\Framework` to register `DisableExtensionsCompilerPass` as compiler pass.
