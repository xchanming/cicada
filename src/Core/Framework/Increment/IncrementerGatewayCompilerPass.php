<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Increment;

use Cicada\Core\Framework\Adapter\Cache\RedisConnectionFactory;
use Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
#[Package('core')]
class IncrementerGatewayCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var array{type?: string, config?: array<string, mixed>}[] $services */
        $services = $container->getParameter('cicada.increment');
        $tag = 'cicada.increment.gateway';

        foreach ($services as $pool => $service) {
            $type = $service['type'] ?? null;

            if (!\is_string($type)) {
                throw IncrementException::wrongGatewayType($pool);
            }

            $active = \sprintf('cicada.increment.%s.gateway.%s', $pool, $type);
            $config = [];

            // If service is not registered directly in the container, try to resolve them using fallback gateway
            if (!$container->hasDefinition($active)) {
                if (\array_key_exists('config', $service)) {
                    $config = (array) $service['config'];
                }

                $active = $this->resolveTypeDefinition($container, $pool, $type, $config);
            }

            if (!$container->hasDefinition($active)) {
                throw IncrementException::gatewayServiceNotFound($type, $pool, $active);
            }

            $definition = $container->getDefinition($active);

            if (!$definition->hasTag($tag)) {
                $definition->addTag($tag);
            }

            $class = $definition->getClass();

            if ($class === null || !is_subclass_of($class, AbstractIncrementer::class)) {
                throw IncrementException::wrongGatewayClass($active, AbstractIncrementer::class);
            }

            $definition->addMethodCall('setPool', [$pool]);
            $definition->addMethodCall('setConfig', [$config]);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveTypeDefinition(ContainerBuilder $container, string $pool, string $type, array $config = []): string
    {
        // cicada.increment.gateway.mysql is fallback gateway if custom gateway is not set
        $fallback = \sprintf('cicada.increment.gateway.%s', $type);

        $gatewayServiceName = \sprintf('cicada.increment.%s.gateway.%s', $pool, $type);

        switch ($type) {
            case 'array':
            case 'mysql':
                $referenceDefinition = $container->getDefinition($fallback);

                $definition = new Definition($referenceDefinition->getClass());
                $definition->setArguments($referenceDefinition->getArguments());
                $definition->setTags($referenceDefinition->getTags());

                $container->setDefinition($gatewayServiceName, $definition);

                return $gatewayServiceName;
            case 'redis':
                $connectionDefinition = new Definition('Redis');

                if (\array_key_exists('connection', $config)) {
                    $connectionDefinition->setFactory([new Reference(RedisConnectionProvider::class), 'getConnection'])->addArgument($config['connection']);
                } elseif (\array_key_exists('url', $config)) { // @deprecated tag:v6.7.0 - remove this elseif block
                    $connectionDefinition->setFactory([new Reference(RedisConnectionFactory::class), 'create'])->addArgument($config['url']);
                    Feature::triggerDeprecationOrThrow(
                        'v6.7.0.0',
                        'Parameter "cicada.increment.pool_name.config.url" for redis is deprecated and will be removed. Please use "cicada.increment.pool_name.config.connection" instead.'
                    );
                } else {
                    return $gatewayServiceName;
                }

                $adapterServiceName = \sprintf('cicada.increment.%s.redis_adapter', $pool);

                $container->setDefinition($adapterServiceName, $connectionDefinition);

                $definition = new Definition(RedisIncrementer::class);
                $definition->addArgument(new Reference($adapterServiceName));

                $container->setDefinition($gatewayServiceName, $definition);

                return $gatewayServiceName;

            default:
                return $gatewayServiceName;
        }
    }
}
