<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart\Command;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCompressor;
use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Cart\CartSerializationCleaner;
use Cicada\Core\Checkout\Cart\Command\CartMigrateCommand;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\RedisCartPersister;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Cache\RedisConnectionFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @internal
 */
#[Package('checkout')]
class CartMigrateCommandTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testWithRedisPrefix(): void
    {
        $url = EnvironmentHelper::getVariable('REDIS_URL');

        if (!$url) {
            static::markTestSkipped('No redis server configured');
        }

        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM cart');

        $redisCart = new Cart(Uuid::randomHex());
        $redisCart->add(
            (new LineItem('A', 'test'))
                ->setPrice(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()))
        );

        $context = $this->getSalesChannelContext($redisCart->getToken());

        $factory = new RedisConnectionFactory('test-prefix-');
        $redis = $factory->create((string) $url);
        static::assertInstanceOf(\Redis::class, $redis);
        $redis->flushAll();

        $persister = new RedisCartPersister($redis, static::getContainer()->get('event_dispatcher'), static::getContainer()->get(CartSerializationCleaner::class), new CartCompressor(false, 'gzip'), 90);
        $persister->save($redisCart, $context);

        $command = new CartMigrateCommand($redis, static::getContainer()->get(Connection::class), 90, $factory, new CartCompressor(false, 'gzip'));
        $command->run(new ArrayInput(['from' => 'redis']), new NullOutput());

        $persister = new CartPersister(
            static::getContainer()->get(Connection::class),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(CartSerializationCleaner::class),
            new CartCompressor(false, 'gzip')
        );

        $persister->load($redisCart->getToken(), $context);
    }

    #[DataProvider('dataProvider')]
    public function testRedisToSql(bool $sqlCompressed, bool $redisCompressed): void
    {
        $url = EnvironmentHelper::getVariable('REDIS_URL');

        if (!$url) {
            static::markTestSkipped('No redis server configured');
        }

        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM cart');

        $redisCart = new Cart(Uuid::randomHex());
        $redisCart->add(
            (new LineItem('A', 'test'))
                ->setPrice(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()))
        );

        $context = $this->getSalesChannelContext($redisCart->getToken());

        $factory = static::getContainer()->get(RedisConnectionFactory::class);
        $redis = $factory->create((string) $url);
        static::assertInstanceOf(\Redis::class, $redis);
        $redis->flushAll();

        $persister = new RedisCartPersister($redis, static::getContainer()->get('event_dispatcher'), static::getContainer()->get(CartSerializationCleaner::class), new CartCompressor($redisCompressed, 'gzip'), 90);
        $persister->save($redisCart, $context);

        $command = new CartMigrateCommand($redis, static::getContainer()->get(Connection::class), 90, $factory, new CartCompressor($redisCompressed, 'gzip'));
        $command->run(new ArrayInput(['from' => 'redis']), new NullOutput());

        $persister = new CartPersister(
            static::getContainer()->get(Connection::class),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(CartSerializationCleaner::class),
            new CartCompressor($sqlCompressed, 'gzip')
        );

        $persister->load($redisCart->getToken(), $context);
    }

    #[DataProvider('dataProvider')]
    public function testSqlToRedis(bool $sqlCompressed, bool $redisCompressed): void
    {
        $url = EnvironmentHelper::getVariable('REDIS_URL');

        if (!$url) {
            static::markTestSkipped('No redis server configured');
        }

        static::getContainer()->get(Connection::class)->executeStatement('DELETE FROM cart');

        $sqlCart = new Cart(Uuid::randomHex());
        $sqlCart->add(
            (new LineItem('A', 'test'))
                ->setPrice(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()))
        );

        $context = $this->getSalesChannelContext($sqlCart->getToken());

        $persister = new CartPersister(
            static::getContainer()->get(Connection::class),
            static::getContainer()->get('event_dispatcher'),
            static::getContainer()->get(CartSerializationCleaner::class),
            new CartCompressor(false, 'gzip')
        );

        $persister->save($sqlCart, $context);

        $token = static::getContainer()->get(Connection::class)->fetchOne('SELECT token FROM cart WHERE token = :token', ['token' => $sqlCart->getToken()]);
        static::assertNotEmpty($token);

        $factory = static::getContainer()->get(RedisConnectionFactory::class);
        $redis = $factory->create((string) $url);
        static::assertInstanceOf(\Redis::class, $redis);
        $redis->flushAll();

        $command = new CartMigrateCommand($redis, static::getContainer()->get(Connection::class), 90, $factory, new CartCompressor($sqlCompressed, 'gzip'));
        $command->run(new ArrayInput(['from' => 'sql']), new NullOutput());

        $persister = new RedisCartPersister($redis, static::getContainer()->get('event_dispatcher'), static::getContainer()->get(CartSerializationCleaner::class), new CartCompressor($redisCompressed, 'gzip'), 90);
        $persister->load($sqlCart->getToken(), $context);
    }

    public static function dataProvider(): \Generator
    {
        yield 'Test sql compressed and redis compressed' => [true, true];
        yield 'Test sql uncompressed and redis uncompressed' => [false, false];
        yield 'Test sql uncompressed and redis compressed' => [false, true];
        yield 'Test sql compressed and redis uncompressed' => [true, false];
    }

    private function getSalesChannelContext(string $token): SalesChannelContext
    {
        return static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL);
    }
}
