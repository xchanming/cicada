<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache\InvalidatorStorage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Adapter\Cache\InvalidatorStorage\RedisInvalidatorStorage;
use Cicada\Core\Test\Stub\Redis\RedisStub;

/**
 * @internal
 */
#[CoversClass(RedisInvalidatorStorage::class)]
class RedisInvalidatorStorageTest extends TestCase
{
    public function testStorage(): void
    {
        $storage = new RedisInvalidatorStorage(new RedisStub());

        static::assertSame($storage->loadAndDelete(), []);

        $storage->store(['foo', 'bar']);

        static::assertSame(['bar', 'foo'], $storage->loadAndDelete());
        static::assertSame([], $storage->loadAndDelete());
    }
}
