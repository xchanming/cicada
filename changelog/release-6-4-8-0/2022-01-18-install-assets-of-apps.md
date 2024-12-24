---
title: Install assets of apps
issue: NEXT-19583
---
# Core
* Added `\Cicada\Core\Framework\Plugin\Util\AssetService::copyAssetsFromApp()`-method to copy assets from Apps to the asset filesystem.
* Changed `\Cicada\Core\Framework\Adapter\Asset\AssetInstallCommand` to also install assets from Apps.
* Changed `\Cicada\Core\Framework\App\Lifecycle\AppLifecycle` to automatically install assets from App on install/update and delete assets on app delete.
