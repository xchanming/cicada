---
title: Introduce app filesystem abstraction
issue: NEXT-36382
author: Aydin Hassan
author_email: a.hassan@cicada.com
author_github: Aydin Hassan
---
# Core
* Deprecated `\Cicada\Core\Framework\App\Exception\AppXmlParsingException::__construct`, use static methods instead
* Deprecated `\Cicada\Core\System\SystemConfig\Exception\ConfigurationNotFoundException`, use `\Cicada\Core\System\SystemConfig\SystemConfigException::configurationNotFound` instead
* Added (internal) `\Cicada\Core\Framework\App\Source\SourceResolver` for accessing a scoped app filesystem
* Added (internal) `\Cicada\Core\Framework\App\Source\Source` interface to handle accessing the different types of app sources
* Added (internal) utilities for validating and extracting apps `\Cicada\Core\Framework\App\AppArchiveValidator` & `\Cicada\Core\Framework\App\AppExtractor`
* Added (internal) filesystem abstraction for scoped access `\Cicada\Core\Framework\Util\Filesystem`
___
# Storefront
* Deprecated `\Cicada\Storefront\Theme\ThemeFileImporterInterface` & `\Cicada\Storefront\Theme\ThemeFileImporter` they will be removed in v6.7.0
* Deprecated `getBasePath` & `setBasePath` methods and `basePath` property on `StorefrontPluginConfiguration` they will be removed in  v6.7.0. Paths are now stored relative to the app/plugin/bundle.
* Added (internal) `\Cicada\Storefront\Theme\ThemeFilesystemResolver` for accessing a scoped filesystem for an instance of `\Cicada\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration`
___
# Next Major Version Changes
## ThemeFileImporterInterface & ThemeFileImporter Removal
Both `\Cicada\Storefront\Theme\ThemeFileImporterInterface` & `\Cicada\Storefront\Theme\ThemeFileImporter` are removed without replacement. These classes are already not used as of v6.6.5.0 and therefore this extension point is removed with no planned replacement.

`getBasePath` & `setBasePath` methods and `basePath` property on `StorefrontPluginConfiguration` are removed. If you need to get the absolute path you should ask for a filesystem instance via `\Cicada\Storefront\Theme\ThemeFilesystemResolver::getFilesystemForStorefrontConfig()` passing in the config object. 
This filesystem instance can read files via a relative path and also return the absolute path of a file. Eg:

```php
$fs = $this->themeFilesystemResolver->getFilesystemForStorefrontConfig($storefrontPluginConfig);
foreach($storefrontPluginConfig->getAssetPaths() as $relativePath) {
    $absolutePath = $fs->path('Resources', $relativePath);
}
```

`\Cicada\Core\System\SystemConfig\Exception\ConfigurationNotFoundException` is removed, if it was previously caught you should change your catch to `\Cicada\Core\System\SystemConfig\SystemConfigException` instead and inspect the code for `\Cicada\Core\System\SystemConfig\SystemConfigException::CONFIG_NOT_FOUND`.
