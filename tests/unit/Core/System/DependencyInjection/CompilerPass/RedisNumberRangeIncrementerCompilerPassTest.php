<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\DependencyInjection\CompilerPass;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\DependencyInjection\CompilerPass\RedisNumberRangeIncrementerCompilerPass;
use Cicada\Core\System\DependencyInjection\DependencyInjectionException;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementRedisStorage;
use Cicada\Core\System\NumberRange\ValueGenerator\Pattern\IncrementStorage\IncrementSqlStorage;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(RedisNumberRangeIncrementerCompilerPass::class)]
class RedisNumberRangeIncrementerCompilerPassTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->addDefinitions([
            IncrementRedisStorage::class => new Definition(),
            'cicada.number_range.redis' => new Definition(),
            IncrementSqlStorage::class => new Definition(),
        ]);
        $this->container->setParameter('cicada.number_range.config.connection', null);
    }

    public function testProcessSql(): void
    {
        $container = $this->container;
        $container->setParameter('cicada.number_range.increment_storage', 'mysql');

        $compilerPass = new RedisNumberRangeIncrementerCompilerPass();
        $compilerPass->process($container);

        static::assertFalse($container->hasDefinition(IncrementRedisStorage::class));
        static::assertFalse($container->hasDefinition('cicada.number_range.redis'));
        static::assertTrue($container->hasDefinition(IncrementSqlStorage::class));
    }

    public function testProcessRedis(): void
    {
        $container = $this->container;
        $container->setParameter('cicada.number_range.increment_storage', 'redis');
        $container->setParameter('cicada.number_range.config.connection', 'my_connection');

        $compilerPass = new RedisNumberRangeIncrementerCompilerPass();
        $compilerPass->process($container);

        static::assertTrue($container->hasDefinition(IncrementRedisStorage::class));
        static::assertTrue($container->hasDefinition('cicada.number_range.redis'));
        static::assertFalse($container->hasDefinition(IncrementSqlStorage::class));
    }

    public function testProcessRedisNoConnection(): void
    {
        $container = $this->container;
        $container->setParameter('cicada.number_range.increment_storage', 'redis');

        self::expectException(DependencyInjectionException::class); // redis connection is not configured
        $compilerPass = new RedisNumberRangeIncrementerCompilerPass();
        $compilerPass->process($container);
    }

    /**
     * @deprecated tag:v6.7.0 - Remove in 6.7
     */
    #[DisabledFeatures(['v6.7.0.0'])]
    public function testProcessRedisWithDsn(): void
    {
        $container = $this->container;
        $container->setParameter('cicada.number_range.increment_storage', 'redis');
        $container->setParameter('cicada.number_range.config.dsn', 'redis://localhost:6379');

        $compilerPass = new RedisNumberRangeIncrementerCompilerPass();
        $compilerPass->process($container);

        static::assertTrue($container->hasDefinition(IncrementRedisStorage::class));
        static::assertTrue($container->hasDefinition('cicada.number_range.redis'));
        static::assertFalse($container->hasDefinition(IncrementSqlStorage::class));
    }
}
