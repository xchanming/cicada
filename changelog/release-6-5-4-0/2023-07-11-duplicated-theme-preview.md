---
title: Fix duplication of theme images
issue: NEXT-25804
---
# Storefront
* Changed `Cicada\Storefront\DependencyInjection\StorefrontMigrationReplacementCompilerPass` by adding `Cicada\Storefront\Migration\V6_5` to migration directories.
  * Added `Cicada\Storefront\Migration\V6_5\Migration1688644407ThemeAddThemeConfig`
    * Added JSON field `theme_json` to table `theme`
* Added for `v6.6.0` method `createFromThemeJson` to `Cicada\Storefront\Theme\StorefrontPluginConfiguration\AbstractStorefrontPluginConfigurationFactory`
* Added property `themeJson` and getter and setter to `\Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration`
* Added method `createFromThemeJson` to `Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationFactory` to create a `StorefrontPluginConfiguration` from the json of the theme in the db.
* Added field `themeJson` to `Cicada\Storefront\Theme\ThemeDefinition`
* Added property `themeJson` to `Cicada\Storefront\Theme\ThemeEntity`
* Changed `\Cicada\Storefront\Theme\ThemeLifecycleService` to compare last installed themeJson version with current to prevent duplicated images.
