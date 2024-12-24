<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Command;

use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Cicada\Core\Content\Media\Commands\GenerateThumbnailsCommand;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Content\Media\Message\UpdateThumbnailsMessage;
use Cicada\Core\Content\Media\Thumbnail\ThumbnailService;
use Cicada\Core\Content\Test\Media\MediaFixtures;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Test\Stub\MessageBus\CollectingMessageBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
class GenerateThumbnailsCommandTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    /**
     * @var EntityRepository<MediaCollection>
     */
    private EntityRepository $mediaRepository;

    /**
     * @var EntityRepository<MediaFolderCollection>
     */
    private EntityRepository $mediaFolderRepository;

    private GenerateThumbnailsCommand $thumbnailCommand;

    private Context $context;

    /**
     * @var array<string>
     */
    private array $initialMediaIds;

    private bool $remoteThumbnailsEnable = false;

    protected function setUp(): void
    {
        $this->mediaRepository = static::getContainer()->get('media.repository');
        $this->mediaFolderRepository = static::getContainer()->get('media_folder.repository');
        $this->context = Context::createDefaultContext();
        $this->remoteThumbnailsEnable = static::getContainer()->getParameter('cicada.media.remote_thumbnails.enable');

        $this->thumbnailCommand = static::getContainer()->get(GenerateThumbnailsCommand::class);

        /** @var array<string> $ids */
        $ids = $this->mediaRepository->searchIds(new Criteria(), $this->context)->getIds();
        $this->initialMediaIds = $ids;
    }

    public function testExecuteHappyPath(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute([]);

        $string = $commandTester->getDisplay();
        static::assertMatchesRegularExpression('/.*Generated\s*2.*/', $string);
        static::assertMatchesRegularExpression('/.*Skipped\s*' . \count($this->initialMediaIds) . '.*/', $string);

        $medias = $this->getNewMediaEntities();
        foreach ($medias as $updatedMedia) {
            $thumbnails = $updatedMedia->getThumbnails();
            static::assertNotNull($thumbnails);
            static::assertEquals(
                2,
                $thumbnails->count()
            );

            foreach ($thumbnails as $thumbnail) {
                $this->assertThumbnailExists($thumbnail);
            }
        }
    }

    public function testExecuteWithCustomLimit(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute(['-b' => '2']);

        $string = $commandTester->getDisplay();
        static::assertMatchesRegularExpression('/.*Generated\s*2.*/', $string);
        static::assertMatchesRegularExpression('/.*Skipped\s*' . \count($this->initialMediaIds) . '.*/', $string);

        $medias = $this->getNewMediaEntities();
        foreach ($medias as $updatedMedia) {
            $thumbnails = $updatedMedia->getThumbnails();
            static::assertNotNull($thumbnails);
            static::assertEquals(
                2,
                $thumbnails->count()
            );

            foreach ($thumbnails as $thumbnail) {
                $this->assertThumbnailExists($thumbnail);
            }
        }
    }

    public function testItSkipsNotSupportedMediaTypes(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->createNotSupportedMediaFiles();

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute([]);

        $string = $commandTester->getDisplay();
        static::assertMatchesRegularExpression('/.*Generated\s*1.*/', $string);
        static::assertMatchesRegularExpression('/.*Skipped\s*' . (\count($this->initialMediaIds) + 1) . '.*/', $string);

        $medias = $this->getNewMediaEntities();
        foreach ($medias as $updatedMedia) {
            if (str_starts_with((string) $updatedMedia->getMimeType(), 'image')) {
                $thumbnails = $updatedMedia->getThumbnails();
                static::assertNotNull($thumbnails);
                static::assertEquals(
                    2,
                    $thumbnails->count()
                );

                foreach ($thumbnails as $thumbnail) {
                    $this->assertThumbnailExists($thumbnail);
                }
            }
        }
    }

    public function testHappyPathWithGivenFolderName(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute(['--folder-name' => 'test folder']);

        $medias = $this->getNewMediaEntities();
        foreach ($medias as $updatedMedia) {
            $thumbnails = $updatedMedia->getThumbnails();
            static::assertNotNull($thumbnails);
            static::assertEquals(2, $thumbnails->count());

            foreach ($thumbnails as $thumbnail) {
                $this->assertThumbnailExists($thumbnail);
            }
        }
    }

    public function testExecuteHappyPathWithRemoteThumbnailsEnable(): void
    {
        if (!$this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is disabled');
        }

        $this->createValidMediaFiles();

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute([]);

        static::assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testSkipsMediaEntitiesFromDifferentFolders(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->createValidMediaFiles();
        $this->mediaFolderRepository->create([
            [
                'name' => 'folder-to-search',
                'useParentConfiguration' => false,
                'configuration' => [],
            ],
        ], $this->context);

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute(['--folder-name' => 'folder-to-search']);

        $medias = $this->getNewMediaEntities();
        foreach ($medias as $updatedMedia) {
            $thumbnails = $updatedMedia->getThumbnails();
            static::assertNotNull($thumbnails);
            static::assertEquals(0, $thumbnails->count());
        }
    }

    public function testCommandAbortsIfNoFolderCanBeFound(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->expectException(MediaException::class);
        $this->expectExceptionMessage('Could not find a folder with name "non-existing-folder"');

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute(['--folder-name' => 'non-existing-folder']);
    }

    public function testItThrowsExceptionOnNonNumericLimit(): void
    {
        if ($this->remoteThumbnailsEnable) {
            static::markTestSkipped('Remote thumbnails is enabled. Skipping thumbnail generation test.');
        }

        $this->expectException(MediaException::class);
        $this->expectExceptionMessage('Provided batch size is invalid.');

        $commandTester = new CommandTester($this->thumbnailCommand);
        $commandTester->execute(['--batch-size' => 'test']);
    }

    public function testItCallsUpdateThumbnailsWithStrictArgument(): void
    {
        $this->createValidMediaFiles();
        $newMedia = $this->getNewMediaEntities();

        $thumbnailServiceMock = $this->getMockBuilder(ThumbnailService::class)
            ->disableOriginalConstructor()->getMock();

        $thumbnailServiceMock->expects(static::exactly(\count($this->initialMediaIds) + $newMedia->count()))
            ->method('updateThumbnails')
            ->with(static::anything(), $this->context, true);

        $command = new GenerateThumbnailsCommand(
            $thumbnailServiceMock,
            $this->mediaRepository,
            $this->mediaFolderRepository,
            static::getContainer()->get('messenger.bus.cicada')
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--strict' => true]);
    }

    public function testItCallsUpdateThumbnailsWithoutStrictArgument(): void
    {
        $this->createValidMediaFiles();
        $newMedia = $this->getNewMediaEntities();

        $thumbnailServiceMock = $this->getMockBuilder(ThumbnailService::class)
            ->disableOriginalConstructor()->getMock();

        $thumbnailServiceMock->expects(static::exactly(\count($this->initialMediaIds) + $newMedia->count()))
            ->method('updateThumbnails')
            ->with(static::anything(), $this->context, false);

        $command = new GenerateThumbnailsCommand(
            $thumbnailServiceMock,
            $this->mediaRepository,
            $this->mediaFolderRepository,
            static::getContainer()->get('messenger.bus.cicada')
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }

    public function testItDispatchesUpdateThumbnailsMessageWithCorrectStrictProperty(): void
    {
        $this->createValidMediaFiles();
        $newMedia = $this->getNewMediaEntities();

        $affectedMediaIds = [...array_combine($this->initialMediaIds, $this->initialMediaIds), ...$newMedia->getIds()];

        $expectedMessageStrict = new UpdateThumbnailsMessage();
        $expectedMessageStrict->setContext($this->context);

        $expectedMessageStrict->setIsStrict(true);
        $expectedMessageStrict->setMediaIds($affectedMediaIds);

        $expectedMessageNonStrict = new UpdateThumbnailsMessage();
        $expectedMessageNonStrict->setContext($this->context);

        $expectedMessageNonStrict->setIsStrict(false);
        $expectedMessageNonStrict->setMediaIds($affectedMediaIds);

        $messageBusMock = new CollectingMessageBus();

        $command = new GenerateThumbnailsCommand(
            static::getContainer()->get(ThumbnailService::class),
            $this->mediaRepository,
            $this->mediaFolderRepository,
            $messageBusMock,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--strict' => true, '--async' => true]);
        $commandTester->execute(['--async' => true]);
        $commandTester->execute(['--async' => true]);
        $commandTester->execute(['--strict' => true, '--async' => true]);

        $envelopes = $messageBusMock->getMessages();
        static::assertCount(4, $envelopes);

        static::assertEquals($expectedMessageStrict, $envelopes[0]->getMessage());
        static::assertEquals($expectedMessageNonStrict, $envelopes[1]->getMessage());
        static::assertEquals($expectedMessageNonStrict, $envelopes[2]->getMessage());
        static::assertEquals($expectedMessageStrict, $envelopes[3]->getMessage());
    }

    protected function assertThumbnailExists(MediaThumbnailEntity $thumbnail): void
    {
        static::assertTrue($this->getPublicFilesystem()->has($thumbnail->getPath()));
    }

    protected function createValidMediaFiles(): void
    {
        $this->setFixtureContext($this->context);
        $mediaPng = $this->getPngWithFolder();
        $mediaJpg = $this->getJpgWithFolder();

        $filePath = $mediaPng->getPath();

        $this->getPublicFilesystem()->writeStream(
            $filePath,
            fopen(__DIR__ . '/../fixtures/cicada-logo.png', 'r')
        );

        $filePath = $mediaJpg->getPath();

        $this->getPublicFilesystem()->writeStream(
            $filePath,
            fopen(__DIR__ . '/../fixtures/cicada.jpg', 'r')
        );
    }

    protected function createNotSupportedMediaFiles(): void
    {
        $this->setFixtureContext($this->context);
        $mediaPdf = $this->getPdf();
        $mediaJpg = $this->getJpgWithFolder();

        $this->mediaRepository->update([
            [
                'id' => $mediaPdf->getId(),
                'mediaFolderId' => $mediaJpg->getMediaFolderId(),
            ],
        ], $this->context);

        $filePath = $mediaPdf->getPath();

        $this->getPublicFilesystem()->writeStream(
            $filePath,
            fopen(__DIR__ . '/../fixtures/small.pdf', 'r')
        );

        $filePath = $mediaJpg->getPath();

        $this->getPublicFilesystem()->writeStream($filePath, fopen(__DIR__ . '/../fixtures/cicada.jpg', 'r'));
    }

    private function getNewMediaEntities(): MediaCollection
    {
        if (!empty($this->initialMediaIds)) {
            $criteria = new Criteria($this->initialMediaIds);
            $result = $this->mediaRepository->searchIds($criteria, $this->context);
            static::assertEquals(\count($this->initialMediaIds), $result->getTotal());
        }

        $criteria = new Criteria();
        $criteria->addAssociation('thumbnails');
        if (!empty($this->initialMediaIds)) {
            $criteria->addFilter(new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsAnyFilter('id', $this->initialMediaIds),
                ]
            ));
        }

        return $this->mediaRepository->search($criteria, $this->context)->getEntities();
    }
}
