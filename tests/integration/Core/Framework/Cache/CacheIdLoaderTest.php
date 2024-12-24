<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Cache;

use Cicada\Core\Framework\Adapter\Cache\CacheIdLoader;
use Cicada\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Cicada\Core\Framework\Test\TestCaseBase\EnvTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('cache')]
class CacheIdLoaderTest extends TestCase
{
    use EnvTestBehaviour;
    use IntegrationTestBehaviour;

    private CacheIdLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = static::getContainer()->get(CacheIdLoader::class);
        $this->setEnvVars(['CICADA_CACHE_ID' => null]);
    }

    public function testCacheIdIsLoadedFromDatabase(): void
    {
        $old = $this->loader->load();

        static::assertTrue(Uuid::isValid($old));

        $new = Uuid::randomHex();

        static::getContainer()->get(AbstractKeyValueStorage::class)->set('cache-id', $new);

        static::assertSame($new, $this->loader->load());

        $this->loader->write($old);

        static::assertSame($old, $this->loader->load());
    }
}
