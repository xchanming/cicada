---
title: Reset translator's cache after switching theme
issue: NEXT-23923
---
# Storefront
* Changed `\Cicada\Storefront\Theme\CachedResolvedConfigLoaderInvalidator::assigned` to invalidate translator cache after switching themes of a sales channel
* Changed `\Cicada\Core\Framework\Adapter\Translation\TranslatorCacheInvalidate::clearCache` to use `CacheInvalidator::invalidate` instead of `CacheItemPoolInterface::deleteItem`
* Changed `\Cicada\Core\Framework\Adapter\Translation\Translator::loadSnippets` to consider `salesChannelId` in cache key when caching translator
