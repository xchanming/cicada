---
title: Recompile theme on plugin with additional bundles
issue: NEXT-35121
---
# Storefront
* Added methods `setAdditionalBundles` and `hasAdditionalBundles` to `\Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration`
* Added `KernelPluginLoader` as constructor parameter to `\Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory`
* Changed method `StorefrontPluginConfigurationFactory::createFromBundle` to set if the Plugin has additionalBundles to the Configuration.
* Added method `pluginPostDeactivate` for event `PluginPostDeactivateEvent` in `\Cicada\Storefront\Theme\Subscriber\PluginLifecycleSubscriber`
* Changed method `pluginDeactivateAndUninstall` to set state `ThemeLifecycleHandler::STATE_SKIP_THEME_COMPILATION` on Plugins with additionalBundles to avoid twice compilation on deactivation.
* Changed method `recompileThemesIfNecessary` in `\Cicada\Storefront\Theme\ThemeLifecycleHandler` to also compile if the Plugin has additional bundles.
