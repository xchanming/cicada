<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\DependencyInjection\CompilerPass;

use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Cart\RedisCartPersister;
use Cicada\Core\Checkout\DependencyInjection\DependencyInjectionException;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @deprecated tag:v6.7.0 - reason:becomes-internal - can be renamed to CartStorageCompilerPass
 */
#[Package('core')]
class CartRedisCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // @deprecated tag:v6.7.0 - remove this if block
        if ($container->hasParameter('cicada.cart.redis_url') && $container->getParameter('cicada.cart.redis_url') !== false) {
            Feature::triggerDeprecationOrThrow(
                'v6.7.0.0',
                'Parameter "cicada.cart.redis_url" is deprecated and will be removed. Please use "cicada.cart.storage.config.connection" instead.'
            );

            $container->setParameter('cicada.cart.storage.config.dsn', $container->getParameter('cicada.cart.redis_url'));
            $container->setParameter('cicada.cart.storage.config.connection', null);

            $container->removeDefinition(CartPersister::class);
            $container->setAlias(CartPersister::class, RedisCartPersister::class);

            return;
        }

        // @deprecated tag:v6.7.0 - remove this if block
        if ($container->hasParameter('cicada.cart.redis_url') && $container->getParameter('cicada.cart.redis_url') === false) {
            $container->removeDefinition('cicada.cart.redis');
            $container->removeDefinition(RedisCartPersister::class);

            return;
        }

        $storage = $container->getParameter('cicada.cart.storage.type');

        switch ($storage) {
            case 'mysql':
                $container->removeDefinition('cicada.cart.redis');
                $container->removeDefinition(RedisCartPersister::class);
                break;
            case 'redis':
                if (
                    !$container->hasParameter('cicada.cart.storage.config.dsn') // @deprecated tag:v6.7.0 - remove this line (as config.dsn will be removed)
                    && $container->getParameter('cicada.cart.storage.config.connection') === null
                ) {
                    throw DependencyInjectionException::redisNotConfiguredForCartStorage();
                }

                $container->removeDefinition(CartPersister::class);
                $container->setAlias(CartPersister::class, RedisCartPersister::class);
                break;
        }

        // @deprecated tag:v6.7.0 - remove this if block
        if (!$container->hasParameter('cicada.cart.storage.config.dsn')) {
            // to avoid changing default values in config or using expression language in service configuration
            $container->setParameter('cicada.cart.storage.config.dsn', null);
        }
    }
}
