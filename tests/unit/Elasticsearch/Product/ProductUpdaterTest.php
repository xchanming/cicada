<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Product;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Events\ProductIndexerEvent;
use Cicada\Core\Content\Product\Events\ProductStockAlteredEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Elasticsearch\Framework\Indexing\ElasticsearchIndexer;
use Cicada\Elasticsearch\Product\ProductUpdater;

/**
 * @internal
 */
#[CoversClass(ProductUpdater::class)]
class ProductUpdaterTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertSame([
            ProductIndexerEvent::class => 'update',
            ProductStockAlteredEvent::class => 'update',
        ], ProductUpdater::getSubscribedEvents());
    }

    public function testUpdate(): void
    {
        $indexer = $this->createMock(ElasticsearchIndexer::class);
        $definition = $this->createMock(EntityDefinition::class);

        $indexer->expects(static::once())->method('updateIds')->with($definition, ['id1', 'id2']);

        $event = new ProductIndexerEvent(['id1', 'id2'], Context::createDefaultContext());

        $updater = new ProductUpdater($indexer, $definition);
        $updater->update($event);
    }

    public function testStockUpdate(): void
    {
        $indexer = $this->createMock(ElasticsearchIndexer::class);
        $definition = $this->createMock(EntityDefinition::class);

        $indexer->expects(static::once())->method('updateIds')->with($definition, ['id1', 'id2']);

        $event = new ProductStockAlteredEvent(['id1', 'id2'], Context::createDefaultContext());

        $updater = new ProductUpdater($indexer, $definition);
        $updater->update($event);
    }
}
