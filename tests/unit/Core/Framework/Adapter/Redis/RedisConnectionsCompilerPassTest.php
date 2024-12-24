<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Redis;

use Cicada\Core\Framework\Adapter\Cache\RedisConnectionFactory;
use Cicada\Core\Framework\Adapter\Redis\RedisConnectionProvider;
use Cicada\Core\Framework\Adapter\Redis\RedisConnectionsCompilerPass;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(RedisConnectionsCompilerPass::class)]
class RedisConnectionsCompilerPassTest extends TestCase
{
    private ContainerBuilder $containerBuilder;

    protected function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
        $connectionProviderDefinition = new Definition(RedisConnectionProvider::class, [
            null,
            new Reference('FakeFactoryClassName'),
        ]);
        $this->containerBuilder->setDefinition(RedisConnectionProvider::class, $connectionProviderDefinition);
    }

    public function testProcessCreatesConnections(): void
    {
        $this->containerBuilder->setParameter('cicada.redis.connections', [
            'db1' => ['dsn' => 'redis://localhost:6379/1'],
            'db2' => ['dsn' => 'redis://localhost:6379/2'],
        ]);

        $compilerPass = new RedisConnectionsCompilerPass();
        $compilerPass->process($this->containerBuilder);

        static::assertTrue($this->containerBuilder->hasDefinition('cicada.redis.connection.db1'));
        static::assertTrue($this->containerBuilder->hasDefinition('cicada.redis.connection.db2'));
        static::assertFalse($this->containerBuilder->hasDefinition('cicada.redis.connection.default'));

        $db1Definition = $this->containerBuilder->getDefinition('cicada.redis.connection.db1');
        static::assertSame('Redis', $db1Definition->getClass());
        static::assertFalse($db1Definition->isLazy());

        $factory = $db1Definition->getFactory();
        static::assertIsArray($factory);
        static::assertCount(2, $factory);
        static::assertInstanceOf(Reference::class, $factory[0]);
        static::assertSame(RedisConnectionFactory::class, (string) $factory[0]);
        static::assertSame('create', $factory[1]);

        static::assertSame('redis://localhost:6379/1', $db1Definition->getArgument(0));
        static::assertFalse($db1Definition->isPublic());

        $db2Definition = $this->containerBuilder->getDefinition('cicada.redis.connection.db2');
        static::assertSame('redis://localhost:6379/2', $db2Definition->getArgument(0));
    }

    public function testProcessConfiguresProvider(): void
    {
        $compilerPass = new RedisConnectionsCompilerPass();
        $compilerPass->process($this->containerBuilder);

        // checking if locator is passed to the provider
        $locatorArgument = $this->containerBuilder->getDefinition(RedisConnectionProvider::class)->getArgument(0);
        static::assertInstanceOf(Reference::class, $locatorArgument);

        // and is created properly
        static::assertTrue($this->containerBuilder->hasDefinition((string) $locatorArgument));
        $locatorDefinition = $this->containerBuilder->getDefinition((string) $locatorArgument);
        $className = $locatorDefinition->getClass();
        static::assertIsString($className);
        $interfaces = class_implements($className);
        static::assertIsArray($interfaces);
        static::assertArrayHasKey(ContainerInterface::class, $interfaces);
    }
}
