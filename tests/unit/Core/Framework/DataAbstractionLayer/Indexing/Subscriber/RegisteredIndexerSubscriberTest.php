<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Indexing\Subscriber;

use Cicada\Core\Content\Media\Infrastructure\Path\MediaPathPostUpdater;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexer;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\Subscriber\RegisteredIndexerSubscriber;
use Cicada\Core\Framework\Migration\IndexerQueuer;
use Cicada\Core\Framework\Store\Event\FirstRunWizardFinishedEvent;
use Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RegisteredIndexerSubscriber::class)]
class RegisteredIndexerSubscriberTest extends TestCase
{
    public function testSendsMessage(): void
    {
        $productIndexer = $this->createMock(ProductIndexer::class);
        $productIndexer->method('getOptions')->willReturn(['seo', 'search', 'other-stuff']);

        $queuer = $this->createMock(IndexerQueuer::class);
        $queuer->expects(static::once())->method('getIndexers')->willReturn(['product.indexer' => ['seo']]);
        $queuer->expects(static::once())->method('finishIndexer')->with(['product.indexer']);

        $indexerRegistry = $this->createMock(EntityIndexerRegistry::class);
        $indexerRegistry->expects(static::once())->method('getIndexer')->with('product.indexer')->willReturn($productIndexer);
        $indexerRegistry->expects(static::once())->method('sendIndexingMessage')->with(['product.indexer'], ['search', 'other-stuff'], true);
        $indexerRegistry->expects(static::never())->method('index');

        $subscriber = new RegisteredIndexerSubscriber(
            $queuer,
            $indexerRegistry
        );
        $subscriber->runRegisteredIndexers();
    }

    public function testSendsMessageWithoutOptions(): void
    {
        $productIndexer = $this->createMock(ProductIndexer::class);
        $productIndexer->method('getOptions')->willReturn(['seo', 'search', 'other-stuff']);

        $queuer = $this->createMock(IndexerQueuer::class);
        $queuer->expects(static::once())->method('getIndexers')->willReturn(['product.indexer' => []]);
        $queuer->expects(static::once())->method('finishIndexer')->with(['product.indexer']);

        $indexerRegistry = $this->createMock(EntityIndexerRegistry::class);
        $indexerRegistry->expects(static::once())->method('getIndexer')->with('product.indexer')->willReturn($productIndexer);
        $indexerRegistry->expects(static::once())->method('sendIndexingMessage')->with(['product.indexer'], [], true);
        $indexerRegistry->expects(static::never())->method('index');

        $subscriber = new RegisteredIndexerSubscriber(
            $queuer,
            $indexerRegistry
        );
        $subscriber->runRegisteredIndexers();
    }

    public function testSendsMessageToSynchronousPostUpdaterIndexer(): void
    {
        $pathPostUpdater = $this->createMock(MediaPathPostUpdater::class);

        $queuer = $this->createMock(IndexerQueuer::class);
        $queuer->expects(static::once())->method('getIndexers')->willReturn(['media.path.post_update' => []]);
        $queuer->expects(static::once())->method('finishIndexer')->with(['media.path.post_update']);

        $indexerRegistry = $this->createMock(EntityIndexerRegistry::class);
        $indexerRegistry->expects(static::once())->method('getIndexer')->with('media.path.post_update')->willReturn($pathPostUpdater);
        $indexerRegistry->expects(static::never())->method('sendIndexingMessage');
        $indexerRegistry->expects(static::once())->method('index')->with(false, [], ['media.path.post_update'], true);

        $subscriber = new RegisteredIndexerSubscriber(
            $queuer,
            $indexerRegistry
        );
        $subscriber->runRegisteredIndexers();
    }

    public function testEmptyQueue(): void
    {
        $queuer = $this->createMock(IndexerQueuer::class);
        $queuer->expects(static::once())->method('getIndexers')->willReturn([]);
        $queuer->expects(static::never())->method('finishIndexer');

        $subscriber = new RegisteredIndexerSubscriber(
            $queuer,
            $this->createMock(EntityIndexerRegistry::class)
        );

        $subscriber->runRegisteredIndexers();
    }

    public function testIgnoresUnknownIndexer(): void
    {
        $productIndexer = $this->createMock(ProductIndexer::class);
        $productIndexer->method('getOptions')->willReturn(['seo', 'search', 'other-stuff']);

        $queuer = $this->createMock(IndexerQueuer::class);
        $queuer->expects(static::once())->method('getIndexers')->willReturn(['product.indexer' => ['seo'], 'unknown.indexer' => []]);
        $queuer->expects(static::once())->method('finishIndexer')->with(['product.indexer', 'unknown.indexer']);

        $indexerRegistry = $this->createMock(EntityIndexerRegistry::class);
        $indexerRegistry
            ->expects(static::exactly(2))
            ->method('getIndexer')
            ->willReturnCallback(static fn (string $name) => $name === 'product.indexer' ? $productIndexer : null);

        $indexerRegistry->expects(static::once())->method('sendIndexingMessage')->with(['product.indexer'], ['search', 'other-stuff']);

        $subscriber = new RegisteredIndexerSubscriber(
            $queuer,
            $indexerRegistry
        );

        $subscriber->runRegisteredIndexers();
    }

    public function testGetSubscribedEvents(): void
    {
        static::assertSame(
            [
                UpdatePostFinishEvent::class => 'runRegisteredIndexers',
                FirstRunWizardFinishedEvent::class => 'runRegisteredIndexers',
            ],
            RegisteredIndexerSubscriber::getSubscribedEvents()
        );
    }
}
