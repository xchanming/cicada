<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\DataAbstractionLayer\StockUpdate;

use Cicada\Core\Content\Product\DataAbstractionLayer\StockUpdate\StockUpdateFilterProvider;
use Cicada\Core\Framework\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(StockUpdateFilterProvider::class)]
class StockUpdateFilterProviderTest extends TestCase
{
    public function testHandlesFilter(): void
    {
        $ids = ['id1', 'id2', 'id3'];

        $filter = new TestStockUpdateFilter(['id1', 'id2']);

        $provider = new StockUpdateFilterProvider([$filter]);

        static::assertEquals(['id3'], $provider->filterProductIdsForStockUpdates($ids, Context::createDefaultContext()));
    }
}
