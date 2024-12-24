---
title: Improved error handling for app lifecycle commands
issue: NEXT-12229
---
# Core
* Changed `\Cicada\Core\Framework\App\Lifecycle\AppLifecycle::install()`-method to throw `\Cicada\Core\Framework\App\Exception\AppAlreadyInstalledException` if the app is already installed.
* Changed `\Cicada\Core\Framework\App\Command\InstallAppCommand` to catch AppAlreadyInstalledException and report that to the user.
* Changed `\Cicada\Core\Framework\App\Command\RefreshAppCommand` to print the reason for install or update failures.
* Deprecated `\Cicada\Core\Framework\App\Lifecycle\AppLifecycleIterator::iterate()`-method, use `\Cicada\Core\Framework\App\Lifecycle\AppLifecycleIterator::iterateOverApps()` instead. 
* Deprecated `\Cicada\Core\Framework\App\AppService::refreshApps()`-method, use `\Cicada\Core\Framework\App\AppService::doRefreshApps()` instead. 
