<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Infrastructure\Command;

use Cicada\Core\Content\Media\Core\Application\MediaLocationBuilder;
use Cicada\Core\Content\Media\Core\Application\MediaPathStorage;
use Cicada\Core\Content\Media\Core\Application\MediaPathUpdater;
use Cicada\Core\Content\Media\Core\Strategy\PlainPathStrategy;
use Cicada\Core\Content\Media\Infrastructure\Command\UpdatePathCommand;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @internal
 */
#[CoversClass(UpdatePathCommand::class)]
class UpdatePathTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    /**
     * @param array<mixed> $media
     * @param array<mixed> $thumbnail
     * @param array<string, string> $expected
     */
    #[DataProvider('commandProvider')]
    public function testCommand(array $media, array $thumbnail, ArrayInput $input, array $expected): void
    {
        $ids = new IdsCollection();

        $queue = new MultiInsertQueryQueue(static::getContainer()->get(Connection::class));

        $media['id'] = $ids->getBytes('media');
        $queue->addInsert('media', $media);

        $thumbnail['id'] = $ids->getBytes('media_thumbnail');
        $thumbnail['media_id'] = $ids->getBytes('media');
        $queue->addInsert('media_thumbnail', $thumbnail);

        $queue->execute();

        $command = new UpdatePathCommand(
            new MediaPathUpdater(
                new PlainPathStrategy(),
                static::getContainer()->get(MediaLocationBuilder::class),
                static::getContainer()->get(MediaPathStorage::class)
            ),
            static::getContainer()->get(Connection::class)
        );

        $command->run($input, new NullOutput());

        $paths = static::getContainer()
            ->get(Connection::class)
            ->fetchAllKeyValue(
                'SELECT LOWER(HEX(id)), path FROM media WHERE id IN (:ids)',
                ['ids' => $ids->getByteList(['media'])],
                ['ids' => ArrayParameterType::BINARY]
            );

        static::assertArrayHasKey($ids->get('media'), $paths);
        static::assertSame($expected['media'], $paths[$ids->get('media')]);

        $paths = static::getContainer()
            ->get(Connection::class)
            ->fetchAllKeyValue(
                'SELECT LOWER(HEX(id)), path FROM media_thumbnail WHERE id IN (:ids)',
                ['ids' => $ids->getByteList(['media_thumbnail'])],
                ['ids' => ArrayParameterType::BINARY]
            );

        static::assertArrayHasKey($ids->get('media_thumbnail'), $paths);
        static::assertSame($expected['thumbnail'], $paths[$ids->get('media_thumbnail')]);
    }

    public static function commandProvider(): \Generator
    {
        yield 'Test generate' => [
            [
                'file_name' => 'test',
                'file_extension' => 'png',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'width' => 100,
                'height' => 100,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            new ArrayInput([]),
            [
                'media' => 'media/test.png',
                'thumbnail' => 'thumbnail/test_100x100.png',
            ],
        ];

        yield 'Test skip generation when path is already set' => [
            [
                'file_name' => 'test',
                'file_extension' => 'png',
                'path' => 'foo/test.png',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'width' => 100,
                'height' => 100,
                'path' => 'foo/test_100x100.png',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            new ArrayInput([]),
            [
                'media' => 'foo/test.png',
                'thumbnail' => 'foo/test_100x100.png',
            ],
        ];

        yield 'Test force parameter overwrites the path' => [
            [
                'file_name' => 'test',
                'file_extension' => 'png',
                'path' => 'foo/test.png',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            [
                'width' => 100,
                'height' => 100,
                'path' => 'foo/test_100x100.png',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
            new ArrayInput(['--force' => true]),
            [
                'media' => 'media/test.png',
                'thumbnail' => 'thumbnail/test_100x100.png',
            ],
        ];
    }
}
