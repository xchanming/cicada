<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Adapter\Cache;

use Cicada\Core\Framework\Adapter\Cache\CacheDecorator;
use Cicada\Core\Framework\Adapter\Cache\CacheTagCollection;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CacheDecorator::class)]
#[Group('cache')]
class CacheDecoratorTest extends TestCase
{
    use IntegrationTestBehaviour;

    private CacheDecorator $cache;

    protected function setUp(): void
    {
        $this->cache = static::getContainer()->get('cache.object');
    }

    public function testTraceSave(): void
    {
        $collection = static::getContainer()->get(CacheTagCollection::class);

        $this->cache->deleteItem('some-key');

        $collection->reset();

        $this->writeItem('some-key', ['tag-a', 'tag-b']);

        static::assertEquals(['tag-a', 'tag-b'], $collection->getTrace('all'));
    }

    public function testTraceRead(): void
    {
        $collection = static::getContainer()->get(CacheTagCollection::class);

        $this->writeItem('some-key', ['tag-a', 'tag-b']);

        $collection->reset();
        $this->cache->getItem('some-key');

        static::assertEquals(['tag-a', 'tag-b'], $collection->getTrace('all'));
    }

    public function testTraceReadAndWrite(): void
    {
        $collection = static::getContainer()->get(CacheTagCollection::class);

        $this->writeItem('some-key-1', ['tag-a', 'tag-b']);
        $this->writeItem('some-key-2', ['tag-c', 'tag-b']);

        $collection->reset();
        $this->cache->getItem('some-key-1');
        $this->cache->getItem('some-key-2');

        $this->writeItem('some-key-3', ['tag-d', 'tag-e']);

        static::assertEquals(['tag-a', 'tag-b', 'tag-c', 'tag-d', 'tag-e'], $collection->getTrace('all'));
    }

    /**
     * @param list<string> $tags
     */
    private function writeItem(string $key, array $tags): void
    {
        $item = $this->cache->getItem($key);
        $item->set($key);
        $item->tag($tags);

        $this->cache->save($item);
    }
}
