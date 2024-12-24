<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache\InvalidatorStorage;

use Cicada\Core\Framework\Adapter\Cache\InvalidatorStorage\RedisInvalidatorStorage;
use Cicada\Core\Test\Stub\Redis\RedisStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
