---
title: Use admin interface language for extension information
issue: NEXT-15586
author: Tobias Berge
author_email: t.berge@cicada.com 
author_github: @tobiasberge
---
# Core
* Added argument `Cicada\Core\Framework\Store\Services\StoreService` to `Cicada\Core\Framework\Store\Services\ExtensionLoader`
* Added argument `user.repository` to `Cicada\Core\Framework\Store\Api\ExtensionStoreDataController`
* Added argument `language.repository` to `Cicada\Core\Framework\Store\Api\ExtensionStoreDataController`
* Added new method `switchContext` to `Cicada\Core\Framework\Store\Api\ExtensionStoreDataController` in order to use the current admin language for the current context when running method `getInstalledExtensions`
* Added argument `Cicada\Core\Framework\Store\Services\StoreService` to `Cicada\Core\Framework\Store\Services\ExtensionLoader`
* Added new optional argument `(string) $locale` to method `loadFromArray` in `Cicada\Core\Framework\Store\Services\ExtensionLoader`
