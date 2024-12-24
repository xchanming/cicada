---
title: Set correct asset path for bundle assets
issue: NEXT-37175
---
# Core
* Changed `\Cicada\Core\Framework\Adapter\Asset\AssetPackageService::create` method to also strip the `bundle` suffix when generating the path, to be in line with `\Cicada\Core\Framework\Plugin\Util\AssetService::getTargetDirectory`, thus fixing usages of `@MyBundle` asset usages.
