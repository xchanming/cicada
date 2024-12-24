<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartCompressor;
use Cicada\Core\Checkout\Cart\CartSerializationCleaner;
use Cicada\Core\Checkout\Cart\Exception\CartTokenNotFoundException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\RedisCartPersister;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Adapter\Cache\RedisConnectionFactory;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\EventDispatcher\CollectingEventDispatcher;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('redis')]
class RedisCartPersisterTest extends TestCase
{
    private RedisCartPersister $persister;

    private \Redis $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $redisUrl = (string) EnvironmentHelper::getVariable('REDIS_URL');

        if ($redisUrl === '') {
            static::markTestSkipped('Redis is not available');
        }

        $factory = new RedisConnectionFactory();

        $client = $factory->create($redisUrl);
        static::assertInstanceOf(\Redis::class, $client);
        $this->redis = $client;
        $this->persister = new RedisCartPersister($this->redis, new CollectingEventDispatcher(), $this->createMock(CartSerializationCleaner::class), new CartCompressor(false, 'gzip'), 30);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->redis->flushAll();
    }

    public function testPersisting(): void
    {
        $token = Uuid::randomHex();
        $cart = new Cart($token);
        $cart->add(new LineItem('test', 'test'));

        $context = $this->createMock(SalesChannelContext::class);

        $this->persister->save($cart, $context);

        $loaded = $this->persister->load($token, $context);

        static::assertEquals($cart->getToken(), $loaded->getToken());
        static::assertEquals($cart->getLineItems(), $loaded->getLineItems());

        $cart->getLineItems()->clear();

        $this->persister->save($cart, $context);

        static::expectException(CartTokenNotFoundException::class);
        $this->persister->load($token, $context);
    }

    public function testDelete(): void
    {
        $token = Uuid::randomHex();
        $cart = new Cart($token);
        $cart->add(new LineItem('test', 'test'));

        $context = $this->createMock(SalesChannelContext::class);

        $this->persister->save($cart, $context);

        $this->persister->load($token, $context);

        $this->persister->delete($token, $context);

        static::expectException(CartTokenNotFoundException::class);
        $this->persister->load($token, $context);
    }

    public function testLoadGzipCompressedCart(): void
    {
        $token = Uuid::randomHex();

        $cart = new Cart($token);
        $compressed = ['content' => gzcompress(serialize(['cart' => $cart, 'rule_ids' => []]), 9), 'compressed' => 1];

        $this->redis->set(RedisCartPersister::PREFIX . $token, serialize($compressed));

        $loaded = $this->persister->load($token, $this->createMock(SalesChannelContext::class));

        static::assertEquals($cart, $loaded);
    }

    public function testLoadZstdCompressedCart(): void
    {
        if (!\function_exists('zstd_compress')) {
            static::markTestSkipped('zstd extension is not installed');
        }

        $token = Uuid::randomHex();

        $cart = new Cart($token);
        $compressed = ['content' => \zstd_compress(serialize(['cart' => $cart, 'rule_ids' => []]), 9), 'compressed' => 2];

        $this->redis->set(RedisCartPersister::PREFIX . $token, serialize($compressed));

        $loaded = $this->persister->load($token, $this->createMock(SalesChannelContext::class));

        static::assertEquals($cart, $loaded);
    }
}
