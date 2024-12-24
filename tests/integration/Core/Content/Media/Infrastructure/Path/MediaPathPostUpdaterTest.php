<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Infrastructure\Path;

use Cicada\Core\Content\Media\Core\Application\MediaLocationBuilder;
use Cicada\Core\Content\Media\Core\Application\MediaPathStorage;
use Cicada\Core\Content\Media\Core\Application\MediaPathUpdater;
use Cicada\Core\Content\Media\Core\Strategy\PlainPathStrategy;
use Cicada\Core\Content\Media\DataAbstractionLayer\MediaIndexingMessage;
use Cicada\Core\Content\Media\Infrastructure\Path\MediaPathPostUpdater;
use Cicada\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MediaPathPostUpdater::class)]
class MediaPathPostUpdaterTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testIterate(): void
    {
        $updater = new MediaPathPostUpdater(
            static::getContainer()->get(IteratorFactory::class),
            static::getContainer()->get(MediaPathUpdater::class),
            static::getContainer()->get(Connection::class),
            static::getContainer()->get(EntityIndexerRegistry::class),
        );

        $ids = new IdsCollection();

        $queue = new MultiInsertQueryQueue(static::getContainer()->get(Connection::class), 250);
        $queue->addInsert('media', ['id' => $ids->getBytes('media-1'), 'file_name' => 'test', 'file_extension' => 'png', 'created_at' => '2021-01-01 00:00:00']);
        $queue->addInsert('media', ['id' => $ids->getBytes('media-2'), 'file_name' => 'test', 'file_extension' => 'png', 'created_at' => '2021-01-01 00:00:00']);
        $queue->addInsert('media', ['id' => $ids->getBytes('media-3'), 'file_name' => 'test', 'path' => 'foo', 'file_extension' => 'png', 'created_at' => '2021-01-01 00:00:00']);

        $queue->execute();

        $message = $updater->iterate(null);

        static::assertNotNull($message);
        // There are some medias, like dummy theme images etc. that are created by the system and can not be cleaned up because of FKs
        $data = $message->getData();
        static::assertIsArray($data);
        static::assertNotEmpty($data);
        static::assertContains($ids->get('media-1'), $data);
        static::assertContains($ids->get('media-2'), $data);
        static::assertContains($ids->get('media-3'), $data);
        static::assertGreaterThanOrEqual(3, $message->getOffset());
    }

    public function testHandle(): void
    {
        $internal = new MediaPathUpdater(
            new PlainPathStrategy(),
            static::getContainer()->get(MediaLocationBuilder::class),
            static::getContainer()->get(MediaPathStorage::class)
        );

        $ids = new IdsCollection();
        $message = new EntityIndexingMessage([$ids->get('media-1'), $ids->get('media-2'), $ids->get('media-3')]);

        $indexerRegistry = $this->createMock(EntityIndexerRegistry::class);
        $indexerRegistry->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function (MediaIndexingMessage $message) use ($ids) {
                // It is expected that indexer is triggered, even if the path was already generated
                static::assertEquals([$ids->get('media-1'), $ids->get('media-2'), $ids->get('media-3')], $message->getData());
                static::assertEquals('media.indexer', $message->getIndexer());

                return true;
            }));

        $updater = new MediaPathPostUpdater(
            static::getContainer()->get(IteratorFactory::class),
            $internal,
            static::getContainer()->get(Connection::class),
            $indexerRegistry
        );

        $queue = new MultiInsertQueryQueue(static::getContainer()->get(Connection::class), 250);
        $queue->addInsert('media', ['id' => $ids->getBytes('media-1'), 'file_name' => 'media-1', 'file_extension' => 'png', 'created_at' => '2021-01-01 00:00:00']);
        $queue->addInsert('media', ['id' => $ids->getBytes('media-2'), 'file_name' => 'media-2', 'file_extension' => 'png', 'created_at' => '2021-01-01 00:00:00']);
        $queue->addInsert('media', ['id' => $ids->getBytes('media-3'), 'file_name' => 'media-3', 'path' => 'already/generated.png', 'file_extension' => 'png', 'created_at' => '2021-01-01 00:00:00']);
        $queue->execute();

        $updater->handle($message);

        $paths = static::getContainer()
            ->get(Connection::class)
            ->fetchFirstColumn(
                'SELECT path FROM media WHERE id IN (:ids)',
                ['ids' => $ids->getByteList(['media-1', 'media-2'])],
                ['ids' => ArrayParameterType::BINARY]
            );

        static::assertCount(2, $paths);
        static::assertContains('media/media-1.png', $paths);
        static::assertContains('media/media-2.png', $paths);
    }
}
