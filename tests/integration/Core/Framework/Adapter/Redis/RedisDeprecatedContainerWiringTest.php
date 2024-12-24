<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Redis;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Increment\RedisIncrementer;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestKernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @phpstan-import-type RedisConnection from RedisConnectionProvider
 *
 * @internal
 *
 * @deprecated tag:v6.7.0 - Remove full class and redis_deprecated_test.yaml config file
 */
#[Package('core')]
#[Group('legacy')]
#[Group('slow')]
class RedisDeprecatedContainerWiringTest extends TestCase
{
    /** @use CustomKernelTestBehavior<RedisDeprecatedTestKernel> */
    use CustomKernelTestBehavior;

    #[IgnoreDeprecations]
    public static function setUpBeforeClass(): void
    {
        $redisUrl = (string) EnvironmentHelper::getVariable('REDIS_URL');
        if ($redisUrl === '') {
            static::markTestSkipped('Redis is not available');
        }

        if (Feature::isActive('v6.7.0.0')) {
            static::markTestSkipped('Test is deprecated and will fail with v6.7');
        }

        self::loadKernel();
    }

    public static function tearDownAfterClass(): void
    {
        self::unloadKernel();
    }

    public function testIncrementGateway(): void
    {
        $container = self::$kernel->getContainer();
        $gatewayRegistry = $container->get('cicada.increment.gateway.registry');
        $gateway = $gatewayRegistry->get('redis_increment');
        static::assertInstanceOf(RedisIncrementer::class, $gateway);
    }

    public function testCacheInvalidatorAdapter(): void
    {
        $container = self::$kernel->getContainer();

        static::assertTrue($container->has('cicada.cache.invalidator.storage.redis_adapter'));
        /** @var RedisConnection $redis */
        $redis = $container->get('cicada.cache.invalidator.storage.redis_adapter');
        self::assertRedisConnectionIsWorking($redis, 'testCacheInvalidatorAdapter');
    }

    public function testNumberRanges(): void
    {
        $container = self::$kernel->getContainer();

        static::assertTrue($container->has('cicada.number_range.redis'));
        /** @var RedisConnection $redis */
        $redis = $container->get('cicada.number_range.redis');
        self::assertRedisConnectionIsWorking($redis, 'testNumberRanges');
    }

    public function testCartRedisConnection(): void
    {
        $container = self::$kernel->getContainer();

        static::assertTrue($container->has('cicada.cart.redis'));
        /** @var RedisConnection $redis */
        $redis = $container->get('cicada.cart.redis');
        self::assertRedisConnectionIsWorking($redis, 'testCartRedisConnection');
    }

    /**
     * @return class-string<RedisDeprecatedTestKernel>
     */
    private static function getKernelClass(): string
    {
        return RedisDeprecatedTestKernel::class;
    }

    /**
     * @param RedisConnection $redis
     */
    private static function assertRedisConnectionIsWorking($redis, string $testString): void
    {
        $key = $testString . '_key';
        $redis->set($key, $testString);
        static::assertEquals($testString, $redis->get($key));
    }
}

/**
 * @deprecated tag:v6.7.0 - Remove in 6.7
 *
 * @internal
 */
class RedisDeprecatedTestKernel extends TestKernel
{
    public function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        $loader->load(__DIR__ . '/../../_snapshots/redis_deprecated_test.yaml');
    }
}
