---
title: Allow plugin configurations as scss variables
issue: NEXT-19595
---
# Storefront
* Added `Context` as parameter for `Cicada\Storefront\Theme\ThemeCompilerInterface::compileTheme`. This will be mandatory in 6.5.0.
* Added `ConfigurationService` and `ActiveAppsLoader` as parameter to `Cicada\Storefront\Theme\ThemeCompiler`
* Deprecated `Cicada\Storefront\Event\ThemeCompilerEnrichScssVariablesEvent` use `Cicada\Storefront\Theme\Event\ThemeCompilerEnrichScssVariablesEvent` instead.
* Added `Cicada\Storefront\Theme\Event\ThemeCompilerEnrichScssVariablesEvent`
* Added `Cicada\Storefront\Theme\Subscriber\ThemeCompilerEnrichScssVarSubscriber`
* Changed `Cicada\Storefront\Theme\StorefrontPluginRegistry` to hold all plugins and apps, not only the themes.
* Deprecated `Cicada\Storefront\Theme\Subscriber\PluginLifecycleSubscriber::pluginActivate` use `\Cicada\Storefront\Theme\Subscriber\PluginLifecycleSubscriber::pluginPostActivate` instead.
___
# Core
* Added `SystemConfigService` as parameters for `\Cicada\Core\System\SystemConfig\Service\ConfigurationService::__construct`
* Added new method `getResolvedConfiguration` to `\Cicada\Core\System\SystemConfig\Service\ConfigurationService` to get the configurations with the current values.
___
# Upgrade Information

## Added new plugin config field

Now you can declare a config field in your plugin `config.xml` to be available as scss variable.
The new tag is `<css>` and takes the name of the scss variable as its value.

```xml
<input-field>
    <name>myPluginBackgroundcolor</name>
    <label>Backgroundcolor</label>
    <label lang="de-DE">Hintergrundfarbe</label>
    <css>my-plugin-background-color</css>
    <defaultValue>#eee</defaultValue>
</input-field>

```
___
# Next Major Version Changes

## Moved and changed the `ThemeCompilerEnrichScssVariablesEvent`
We moved the event `ThemeCompilerEnrichScssVariablesEvent` from `\Cicada\Storefront\Event\ThemeCompilerEnrichScssVariablesEvent` to `\Cicada\Storefront\Theme\Event\ThemeCompilerEnrichScssVariablesEvent`.
Please use the new event now.

## Method `pluginActivate` in `PluginLifecycleSubscriber` will be exchanged with new method `pluginPostActivate`
We exchanged the method `pluginActivate` in `PluginLifecycleSubscriber` and will now use the `pluginPostActivate` with the `PluginPostActivateEvent`.

