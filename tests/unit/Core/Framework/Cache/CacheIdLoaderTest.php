<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Cache;

use Cicada\Core\Framework\Adapter\Cache\CacheIdLoader;
use Cicada\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CacheIdLoader::class)]
class CacheIdLoaderTest extends TestCase
{
    use EnvTestBehaviour;

    private AbstractKeyValueStorage&MockObject $storage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(AbstractKeyValueStorage::class);
        $this->setEnvVars(['CICADA_CACHE_ID' => null]);
    }

    public function testLoadExisting(): void
    {
        $id = Uuid::randomHex();
        $this->storage->method('get')->willReturn($id);

        $loader = new CacheIdLoader($this->storage);

        static::assertSame($id, $loader->load());
    }

    public function testMissingCacheIdWritesId(): void
    {
        $this->storage->method('get')->willReturn(false);

        $loader = new CacheIdLoader($this->storage);

        static::assertTrue(Uuid::isValid($loader->load()));
    }

    public function testCacheIdIsNotAString(): void
    {
        $this->storage->method('get')->willReturn(0);

        $loader = new CacheIdLoader($this->storage);

        static::assertTrue(Uuid::isValid($loader->load()));
    }
}
