<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\ReverseProxy;

use Cicada\Core\Framework\Adapter\Cache\Http\CacheStore;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('storefront')]
class ReverseProxyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('cicada.http_cache.reverse_proxy.enabled')) {
            $container->removeDefinition('cicada.cache.reverse_proxy.redis');
            $container->removeDefinition(ReverseProxyCache::class);
            $container->removeDefinition(AbstractReverseProxyGateway::class);
            $container->removeDefinition(FastlyReverseProxyGateway::class);
            $container->removeDefinition(ReverseProxyCacheClearer::class);
            $container->removeDefinition(FastlyReverseProxyGateway::class);

            return;
        }

        $container->removeDefinition(CacheStore::class);

        $container->setAlias(CacheStore::class, ReverseProxyCache::class);
        $container->getAlias(CacheStore::class)->setPublic(true);

        if ($container->getParameter('cicada.http_cache.reverse_proxy.fastly.enabled')) {
            $container->setAlias(AbstractReverseProxyGateway::class, FastlyReverseProxyGateway::class);
        } elseif ($container->getParameter('cicada.http_cache.reverse_proxy.use_varnish_xkey')) {
            $container->setAlias(AbstractReverseProxyGateway::class, VarnishReverseProxyGateway::class);
        }
    }
}
