<?php declare(strict_types=1);

namespace Cicada\Core\System\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\DependencyInjection\DependencyInjectionException;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementRedisStorage;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementSqlStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @deprecated tag:v6.7.0 - reason:becomes-internal - can be renamed to NumberRangeIncrementStorageCompilerPass
 */
#[Package('core')]
class RedisNumberRangeIncrementerCompilerPass implements CompilerPassInterface
{
    private const DEPRECATED_MAPPING = [
        'SQL' => 'mysql',
        'Redis' => 'redis',
    ];

    public function process(ContainerBuilder $container): void
    {
        $storage = $container->getParameter('cicada.number_range.increment_storage');

        // @deprecated tag:v6.7.0 - remove this if block
        if (\in_array($storage, array_keys(self::DEPRECATED_MAPPING), true)) {
            Feature::triggerDeprecationOrThrow('v6.7.0.0', \sprintf(
                'Parameter value "%s" will not be supported. Please use one of the following values: %s',
                $storage,
                implode(', ', self::DEPRECATED_MAPPING)
            ));

            $container->setParameter('cicada.number_range.increment_storage', self::DEPRECATED_MAPPING[$storage]);
        }

        // @deprecated tag:v6.7.0 - remove this if block
        if ($container->hasParameter('cicada.number_range.redis_url') && $container->getParameter('cicada.number_range.redis_url') !== false) {
            Feature::triggerDeprecationOrThrow(
                'v6.7.0.0',
                'Parameter "cicada.number_range.redis_url" is deprecated and will be removed. Please use "cicada.number_range.config.name" instead.'
            );

            $container->setParameter('cicada.number_range.config.dsn', $container->getParameter('cicada.number_range.redis_url'));
        }

        // @deprecated tag:v6.7.0 - remove this if block
        if ($container->hasParameter('cicada.number_range.config.dsn') && $container->getParameter('cicada.number_range.config.dsn') !== false) {
            Feature::triggerDeprecationOrThrow(
                'v6.7.0.0',
                'Parameter "cicada.number_range.config.dsn" is deprecated and will be removed. Please use "cicada.number_range.config.connection" instead.'
            );
        }

        switch ($storage) {
            case 'SQL': // @deprecated tag:v6.7.0 - remove this case
                $container->removeDefinition('cicada.number_range.redis');
                $container->removeDefinition(IncrementRedisStorage::class);
                break;
            case 'Redis': // @deprecated tag:v6.7.0 - remove this case
                if (!$container->hasParameter('cicada.number_range.config.dsn')) {
                    throw DependencyInjectionException::redisNotConfiguredForNumberRangeIncrementer();
                }

                $container->removeDefinition(IncrementSqlStorage::class);
                break;
            case 'mysql':
                $container->removeDefinition('cicada.number_range.redis');
                $container->removeDefinition(IncrementRedisStorage::class);
                break;
            case 'redis':
                if (
                    !$container->hasParameter('cicada.number_range.config.dsn') // @deprecated tag:v6.7.0 - remove this line (as config.dsn will be removed)
                    && $container->getParameter('cicada.number_range.config.connection') === null
                ) {
                    throw DependencyInjectionException::redisNotConfiguredForNumberRangeIncrementer();
                }

                $container->removeDefinition(IncrementSqlStorage::class);
                break;
        }

        // @deprecated tag:v6.7.0 - remove this if block
        if (!$container->hasParameter('cicada.number_range.config.dsn')) {
            // to avoid changing default values in config or using expression language in service configuration
            $container->setParameter('cicada.number_range.config.dsn', null);
        }
    }
}
