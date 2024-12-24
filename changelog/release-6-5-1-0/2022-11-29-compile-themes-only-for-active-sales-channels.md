---
title: Compile theme only for active sales channels
issue: NEXT-25596
author: Lars Schröder (KielCoding)
author_email: lars.schroeder@kielcoding.de
author_github: larsbo
---
# Storefront
* Deprecated `\Cicada\Storefront\Theme\ConfigLoader\AbstractAvailableThemeProvider::load`. Parameter `$activeOnly` will be introduced in a future version.
* Added optional second parameter `$activeOnly` to `\Cicada\Storefront\Theme\ConfigLoader\DatabaseAvailableThemeProvider::load`
* Deprecated `\Cicada\Storefront\Theme\ConfigLoader\DatabaseAvailableThemeProvider::load`. Second parameter `$activeOnly` will be required in future versions.
* Added optional second parameter `$activeOnly` to `\Cicada\Storefront\Theme\ConfigLoader\StaticFileAvailableThemeProvider::load`
* Deprecated `\Cicada\Storefront\Theme\ConfigLoader\StaticFileAvailableThemeProvider::load`. Second parameter `$activeOnly` will be required in future versions.
* Added option `active-only` to `\Cicada\Storefront\Theme\Command\ThemeCompileCommand::__construct`
