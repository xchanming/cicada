---
title: Services stability improvements
issue: NEXT-38322
---
___
# Storefront
* Deprecated `Cicada\Storefront\Theme\StorefrontPluginRegistry` - It will become internal and not implement `\Cicada\Storefront\Theme\StorefrontPluginRegistryInterface`
* Deprecated `Cicada\Storefront\Theme\StorefrontPluginRegistryInterface` - It will be removed without replacement
* Changed `Cicada\Storefront\Theme\StorefrontPluginRegistry` to ignore services
___
# Upgrade Information
## Internalisation of StorefrontPluginRegistry & Removal of StorefrontPluginRegistryInterface

The class `Cicada\Storefront\Theme\StorefrontPluginRegistry` will become internal and will no longer implement `Cicada\Storefront\Theme\StorefrontPluginRegistryInterface`.

The interface `Cicada\Storefront\Theme\StorefrontPluginRegistryInterface` will be removed.

Please refactor your code to not use this class & interface.
___
## Internalisation of StorefrontPluginRegistry & Removal of StorefrontPluginRegistryInterface

The class `Cicada\Storefront\Theme\StorefrontPluginRegistry` is now internal and does not implement `Cicada\Storefront\Theme\StorefrontPluginRegistryInterface`.

The interface `Cicada\Storefront\Theme\StorefrontPluginRegistryInterface` has been removed.

Please refactor your code to not use this class & interface.
