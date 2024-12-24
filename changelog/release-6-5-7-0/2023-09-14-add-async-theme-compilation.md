---
title: Added async theme compilation configuration
issue: NEXT-29828
author: Stefan Sluiter
author_email: s.sluiter@cicada.com
---
# Storefront
* Changed `sw-settings-storefront-configuration.html.twig` and `modules/sw-settings-storefront/page/sw-settings-storefront-index/index.js` to add async compilatin setting.
* Added `Cicada\Storefront\Theme\Message\CompileThemeMessage` for theme compiling messages.
* Added `Cicada\Storefront\Theme\Message\CompileThemeHandler` as a handler for `Cicada\Storefront\Theme\Message\CompileThemeMessage` messages.
* Changed `Cicada\Storefront\Theme\ThemeService::compileTheme` and `Cicada\Storefront\Theme\ThemeService::compileThemeById` to check whether the compiling should be done asynchronously.
* Changed `Cicada\Storefront\Theme\ThemeService` by adding `reset` method.
* Changed `Cicada\Storefront\Theme\ThemeService` to implement the ResetInterface
___
# Upgrade Information
## Async theme compilation (@experimental)

It is now possible to trigger the compilation of the storefront css and js via the message queue instead of directly 
inside the call that changes the theme or activates/deactivates an extension.

You can change the compilation type with the system_config key `core.storefrontSettings.asyncThemeCompilation` in the 
administration (`settings -> system -> storefront`)
