---
title: Add caching for script endpoints
issue: NEXT-19812
---
# Core
* Added `\Cicada\Core\Framework\Adapter\Cache\Script` domain, to allow apps to invalidate the cache.
* Added `cache-invalidation` hook for app scripts.
* Added `md5` filter to the `\Cicada\Core\Framework\Adapter\Twig\Extension\PhpSyntaxExtension`, to allow easy generation of md5-hashes from inside twig.
* Added `\Cicada\Core\Framework\Script\Api\ResponseCacheConfiguration` to configure the caching of script responses.
* Added `store-api-{hook}-cache-key` hook for app scripts, to calculate the cache-key of a request, without producing a response.
* Changed `\Cicada\Core\Framework\Script\Api\ScriptStoreApiRoute` to handle caching of responses based on the cache-key and the cache config the app scripts provide.
___
# Storefront
* Changed `\Cicada\Storefront\Controller\ScriptController` to integrate with the Http-Cache based on the cache config the script provides.
* Changed `\Cicada\Storefront\Framework\Cache\CacheStore` to allow adding custom cache tags to responses over a header
