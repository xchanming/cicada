---
title: Fix plugin refresh if root composer.json is a plugin
issue: NEXT-36082
author: Alexander Stehlik
author_email: alexander.stehlik@gmail.com
author_github: astehlik
---
# Core
* Changed behavior of `\Cicada\Core\Framework\Plugin\Util\PluginFinder`: it now returns an absolute path when a Cicada plugin is detected in the `composer.json` file in the project root. This prevents an error in the `refreshPlugins()` method of the `\Cicada\Core\Framework\Plugin\PluginService` which expects all plugin paths to be absolute.
