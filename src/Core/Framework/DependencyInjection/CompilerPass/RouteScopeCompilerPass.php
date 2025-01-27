<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\AbstractRouteScope;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[Package('framework')]
class RouteScopeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $routeScopeDefinitions = $container->findTaggedServiceIds('cicada.route_scope');

        $apiPrefixes = [];
        foreach (array_keys($routeScopeDefinitions) as $definition) {
            $routeScope = $container->get($definition);

            if (!$routeScope instanceof AbstractRouteScope) {
                continue;
            }

            $apiPrefixes = array_merge($apiPrefixes, $routeScope->getRoutePrefixes());
        }

        $container->setParameter('cicada.routing.registered_api_prefixes', $apiPrefixes);
    }
}
