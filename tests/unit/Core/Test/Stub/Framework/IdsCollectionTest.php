<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Test\Stub\Framework;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(IdsCollection::class)]
class IdsCollectionTest extends TestCase
{
    public function testIdsCollection(): void
    {
        $ids = new IdsCollection();
        $id = $ids->create('test');

        $ids->set('foo', $id);

        static::assertEquals($id, $ids->get('foo'));
        static::assertEquals($id, $ids->get('test'));
        static::assertEquals([$id], array_values($ids->getList(['test'])));
        static::assertEquals([['id' => $id]], $ids->getIdArray(['test']));
        static::assertEquals(Uuid::fromHexToBytes($id), $ids->getBytes('test'));
        static::assertEquals([['id' => $id]], $ids->getIdArray(['test']));
    }
}
