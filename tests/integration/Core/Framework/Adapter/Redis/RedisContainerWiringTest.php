<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Redis;

use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider;
use Cicada\Core\Framework\Increment\RedisIncrementer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestKernel;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @phpstan-import-type RedisConnection from RedisConnectionProvider
 *
 * @internal
 */
#[Package('core')]
#[Group('slow')]
class RedisContainerWiringTest extends TestCase
{
    /** @use CustomKernelTestBehavior<RedisTestKernel> */
    use CustomKernelTestBehavior;

    public static function setUpBeforeClass(): void
    {
        $redisUrl = (string) EnvironmentHelper::getVariable('REDIS_URL');
        if ($redisUrl === '') {
            static::markTestSkipped('Redis is not available');
        }

        self::loadKernel();
    }

    public static function tearDownAfterClass(): void
    {
        self::unloadKernel();
    }

    public function testRedisConnections(): void
    {
        // Fetch the container
        $container = self::$kernel->getContainer();
        $redisUrl = (string) EnvironmentHelper::getVariable('REDIS_URL');

        // Validate config is read correctly
        static::assertTrue($container->hasParameter('cicada.redis.connections.ephemeral.dsn'));
        static::assertEquals($redisUrl, $container->getParameter('cicada.redis.connections.ephemeral.dsn'));

        // Validate that connection provider is correctly set
        static::assertTrue($container->has(RedisConnectionProvider::class));
        $redisProvider = $container->get(RedisConnectionProvider::class);
        static::assertInstanceOf(RedisConnectionProvider::class, $redisProvider);

        // Validate that connections are correctly set
        static::assertTrue($redisProvider->hasConnection('ephemeral'));
        static::assertTrue($redisProvider->hasConnection('persistent'));

        // Validate that connections are correctly created
        /** @var RedisConnection $redis */
        $redis = $redisProvider->getConnection('ephemeral');
        $info = $redis->info();
        static::assertIsArray($info);
        static::assertArrayHasKey('total_connections_received', $info);
    }

    public function testIncrementGateway(): void
    {
        $container = self::$kernel->getContainer();
        $gatewayRegistry = $container->get('cicada.increment.gateway.registry');
        $gateway = $gatewayRegistry->get('redis_increment');
        static::assertInstanceOf(RedisIncrementer::class, $gateway);

        // run operations
        $gateway->increment('test', 'test');
        $list1 = $gateway->list('test');
        $gateway->increment('test', 'test');
        $list2 = $gateway->list('test');

        static::assertArrayHasKey('test', $list1);
        static::assertArrayHasKey('test', $list2);
        static::assertArrayHasKey('count', $list1['test']);
        static::assertArrayHasKey('count', $list2['test']);

        // Compare the 'count' values
        static::assertEquals($list1['test']['count'] + 1, $list2['test']['count']);
    }

    public function testCacheInvalidatorAdapter(): void
    {
        $container = self::$kernel->getContainer();

        static::assertTrue($container->has('cicada.cache.invalidator.storage.redis_adapter'));
        $redis = $container->get('cicada.cache.invalidator.storage.redis_adapter');

        $redisProvider = $container->get(RedisConnectionProvider::class);
        static::assertInstanceOf(RedisConnectionProvider::class, $redisProvider);
        static::assertSame($redis, $redisProvider->getConnection('ephemeral'));
    }

    public function testNumberRanges(): void
    {
        $container = self::$kernel->getContainer();

        static::assertTrue($container->has('cicada.number_range.redis'));
        $redis = $container->get('cicada.number_range.redis');

        $redisProvider = $container->get(RedisConnectionProvider::class);
        static::assertInstanceOf(RedisConnectionProvider::class, $redisProvider);
        static::assertSame($redis, $redisProvider->getConnection('persistent'));
    }

    public function testCartRedisConnection(): void
    {
        $container = self::$kernel->getContainer();

        static::assertTrue($container->has('cicada.cart.redis'));
        $redis = $container->get('cicada.cart.redis');

        $redisProvider = $container->get(RedisConnectionProvider::class);
        static::assertInstanceOf(RedisConnectionProvider::class, $redisProvider);
        static::assertSame($redis, $redisProvider->getConnection('persistent'));
    }

    /**
     * @return class-string<RedisTestKernel>
     */
    private static function getKernelClass(): string
    {
        return RedisTestKernel::class;
    }
}

/**
 * @internal
 */
class RedisTestKernel extends TestKernel
{
    public function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $loader->load(__DIR__ . '/../../_snapshots/redis_test.yaml');
    }
}
