---
title: Remove Storefront deprecations
issue: NEXT-32085
---
# Storefront
* Removed deprecated exception class `VerificationHashNotConfiguredException`
* Changed method `Cicada\Storefront\Theme\StorefrontPluginConfiguration\AbstractStorefrontPluginConfigurationFactory::createFromThemeJson` to abstract.
* Changed parameter `$basePath` in `Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration` to be no more nullable.
* Changed parameter `$pluginConfigurationFactory` in constructor of `Cicada\Storefront\Theme\ThemeLifecycleService` to be mandatory.
