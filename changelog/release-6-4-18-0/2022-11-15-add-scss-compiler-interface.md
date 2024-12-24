---
title: Add SCSS compiler interface
issue: NEXT-23976
---
# Storefront
* Added `Cicada\Storefront\Theme\AbstractScssCompiler` as a blueprint for custom scss compilers
* Added `Cicada\Storefront\Theme\ScssPhpCompiler` as a wrapper for `\ScssPhp\ScssPhp\Compiler`
* Added `AbstractScssCompiler` as argument for the constructor of `Cicada\Storefront\Theme\ThemeCompiler`
