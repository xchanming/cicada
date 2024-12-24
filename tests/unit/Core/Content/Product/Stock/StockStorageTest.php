<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Stock;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Stock\StockLoadRequest;
use Cicada\Core\Content\Product\Stock\StockStorage;
use Cicada\Core\Framework\Context;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(StockStorage::class)]
class StockStorageTest extends TestCase
{
    public function testLoadDoesNothing(): void
    {
        $ids = new IdsCollection();

        $productIds = $ids->getList(['p-1', 'p-2', 'p-3']);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $connection = $this->createMock(Connection::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $stockStorage = new StockStorage($connection, $dispatcher);

        static::assertEquals(
            [],
            $stockStorage->load(new StockLoadRequest(array_values($productIds)), $salesChannelContext)->all()
        );
    }

    public function testEmptyChangesDoNotDispatchEvent(): void
    {
        $connection = $this->createMock(Connection::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $dispatcher->expects(static::never())->method('dispatch');

        $stockStorage = new StockStorage($connection, $dispatcher);
        $stockStorage->alter([], Context::createDefaultContext());
    }
}
