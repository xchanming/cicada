---
title: Improve composer executions during plugin lifecycle
issue: NEXT-36780
---

# Core

* Changed `\Cicada\Core\Framework\Plugin\Composer\CommandExecutor` to update only directly affected packages instead of all packages.
* Changed `\Cicada\Core\Framework\Plugin\PluginLifecycleService` to not modify vendor directory if Cicada is in cluster mode.
