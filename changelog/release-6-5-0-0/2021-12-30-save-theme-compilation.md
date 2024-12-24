---
title: Save theme compilation
issue: NEXT-15381
---

# Storefront

* Added event `Cicada\Storefront\Theme\Event\ThemeCopyToLiveEvent`
* Added Exception `Cicada\Storefront\Theme\Exception\ThemeFileCopyException`
* Changed method `Cicada\Storefront\Theme\ThemeCompiler::compileTheme` to compile in a temporary directory and only move the new compiled files to live if the compilation was successful.
