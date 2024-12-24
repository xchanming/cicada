---
title: Http Cache Reverse Proxy support
issue: NEXT-12958
---

# Storefront

* Added following new classes:
    * `Cicada\Storefront\DependencyInjection\ReverseProxyCompilerPass`
    * `Cicada\Storefront\Framework\Cache\ReverseProxy\AbstractReverseProxyGateway`
    * `Cicada\Storefront\Framework\Cache\ReverseProxy\RedisReverseProxyGateway`
    * `Cicada\Storefront\Framework\Cache\ReverseProxy\ReverseProxyCache`
* Added new configuration in the `storefront.yaml` for reverse http cache
