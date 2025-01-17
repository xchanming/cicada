<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Message;

use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Media\Message\GenerateThumbnailsHandler;
use Cicada\Core\Content\Media\Message\GenerateThumbnailsMessage;
use Cicada\Core\Content\Media\Message\UpdateThumbnailsMessage;
use Cicada\Core\Content\Media\Thumbnail\ThumbnailService;
use Cicada\Core\Content\Test\Media\MediaFixtures;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class GenerateThumbnailsHandlerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    /**
     * @var EntityRepository<MediaCollection>
     */
    private EntityRepository $mediaRepository;

    /**
     * @var EntityRepository<MediaThumbnailCollection>
     */
    private EntityRepository $thumbnailRepository;

    private Context $context;

    private GenerateThumbnailsHandler $handler;

    private bool $remoteThumbnailsEnable = false;

    protected function setUp(): void
    {
        $this->mediaRepository = static::getContainer()->get('media.repository');
        $this->thumbnailRepository = static::getContainer()->get('media_thumbnail.repository');
        $this->context = Context::createDefaultContext();

        $this->handler = static::getContainer()->get(GenerateThumbnailsHandler::class);

        $this->remoteThumbnailsEnable = static::getContainer()->getParameter('cicada.media.remote_thumbnails.enable');
    }

    public function testGenerateThumbnails(): void
    {
        if ($this->remoteThumbnailsEnable) {
            return;
        }

        $this->setFixtureContext($this->context);
        $media = $this->getPngWithFolder();

        $this->thumbnailRepository->create([
            [
                'mediaId' => $media->getId(),
                'width' => 987,
                'height' => 987,
            ],
            [
                'mediaId' => $media->getId(),
                'width' => 150,
                'height' => 150,
            ],
        ], $this->context);

        /** @var MediaEntity $media */
        $media = $this->mediaRepository->search(new Criteria([$media->getId()]), $this->context)->get($media->getId());

        $this->getPublicFilesystem()->writeStream(
            $media->getPath(),
            fopen(__DIR__ . '/../fixtures/cicada-logo.png', 'r')
        );

        $msg = new GenerateThumbnailsMessage();
        $msg->setMediaIds([$media->getId()]);
        $msg->setContext($this->context);

        $this->handler->__invoke($msg);

        $criteria = new Criteria([$media->getId()]);
        $criteria->addAssociation('thumbnails');

        /** @var MediaEntity $media */
        $media = $this->mediaRepository->search($criteria, $this->context)->get($media->getId());
        $mediaThumbnailCollection = $media->getThumbnails();
        static::assertNotNull($mediaThumbnailCollection);
        static::assertEquals(2, $mediaThumbnailCollection->count());

        foreach ($mediaThumbnailCollection as $thumbnail) {
            static::assertTrue(
                ($thumbnail->getWidth() === 300 && $thumbnail->getHeight() === 300)
                || ($thumbnail->getWidth() === 150 && $thumbnail->getHeight() === 150)
            );

            $path = $thumbnail->getPath();
            static::assertTrue(
                $this->getPublicFilesystem()->has($path),
                'Thumbnail: ' . $path . ' does not exist'
            );
        }
    }

    public function testUpdateThumbnails(): void
    {
        if ($this->remoteThumbnailsEnable) {
            return;
        }

        $this->setFixtureContext($this->context);
        $media = $this->getPngWithFolder();

        $this->thumbnailRepository->create([
            [
                'mediaId' => $media->getId(),
                'width' => 987,
                'height' => 987,
            ],
        ], $this->context);

        /** @var MediaEntity $media */
        $media = $this->mediaRepository->search(new Criteria([$media->getId()]), $this->context)->get($media->getId());

        $url = $media->getPath();

        $this->getPublicFilesystem()->writeStream(
            $url,
            fopen(__DIR__ . '/../fixtures/cicada-logo.png', 'r')
        );

        $msg = new UpdateThumbnailsMessage();
        $msg->setMediaIds([$media->getId()]);
        $msg->setContext($this->context);

        $this->handler->__invoke($msg);

        $criteria = new Criteria([$media->getId()]);
        $criteria->addAssociation('thumbnails');
        $criteria->addAssociation('mediaFolder.configuration.thumbnailSizes');

        /** @var MediaEntity $media */
        $media = $this->mediaRepository->search($criteria, $this->context)->get($media->getId());
        $mediaThumbnailCollection = $media->getThumbnails();
        static::assertNotNull($mediaThumbnailCollection);
        static::assertEquals(2, $mediaThumbnailCollection->count());

        foreach ($mediaThumbnailCollection as $thumbnail) {
            static::assertTrue(
                ($thumbnail->getWidth() === 300 && $thumbnail->getHeight() === 300)
                || ($thumbnail->getWidth() === 150 && $thumbnail->getHeight() === 150)
            );

            $path = $thumbnail->getPath();
            static::assertTrue(
                $this->getPublicFilesystem()->has($path),
                'Thumbnail: ' . $path . ' does not exist'
            );
        }
    }

    public function testDiffersBetweenUpdateAndGenerateMessage(): void
    {
        if ($this->remoteThumbnailsEnable) {
            return;
        }

        $thumbnailServiceMock = $this->getMockBuilder(ThumbnailService::class)
            ->disableOriginalConstructor()->getMock();

        $handler = new GenerateThumbnailsHandler($thumbnailServiceMock, $this->mediaRepository);

        $randomCriteria = (new Criteria())
            /* @see GenerateThumbnailsHandler Association as in target method is required for the ease of PHPUnit's constraint evaluation */
            ->addAssociation('mediaFolder.configuration.mediaThumbnailSizes')
            ->setLimit(5);

        $testEntities1 = $this->mediaRepository->search($randomCriteria->setOffset(0), $this->context)->getEntities();
        $testEntities2 = $this->mediaRepository->search($randomCriteria->setOffset(5), $this->context)->getEntities();
        $testEntities3 = $this->mediaRepository->search($randomCriteria->setOffset(10), $this->context)->getEntities();

        $generateMessage = new GenerateThumbnailsMessage();
        $generateMessage->setMediaIds($testEntities1->getIds());
        $generateMessage->setContext($this->context);

        $updateMessage1 = new UpdateThumbnailsMessage();
        $updateMessage1->setMediaIds($testEntities2->getIds());
        $updateMessage1->setStrict(true);
        $updateMessage1->setContext($this->context);

        $updateMessage2 = new UpdateThumbnailsMessage();
        $updateMessage2->setMediaIds($testEntities3->getIds());
        $updateMessage2->setStrict(false);
        $updateMessage2->setContext($this->context);

        $thumbnailServiceMock->expects(static::once())
            ->method('generate')
            ->with($testEntities1, $this->context)
            ->willReturn($testEntities1->count());

        $consecutiveUpdateMessageParams = [
            // For UpdateMessage 1
            ...array_map(fn ($entity) => [$entity, $this->context, true], array_values($testEntities2->getElements())),
            // For UpdateMessage 2
            ...array_map(fn ($entity) => [$entity, $this->context, false], array_values($testEntities3->getElements())),
        ];

        $parameters = [];

        $thumbnailServiceMock->expects(static::exactly($testEntities2->count() + $testEntities3->count()))
            ->method('updateThumbnails')
            ->willReturnCallback(function (...$params) use (&$parameters): void {
                $parameters[] = $params;
            });

        $handler->__invoke($generateMessage);
        $handler->__invoke($updateMessage1);
        $handler->__invoke($updateMessage2);

        static::assertEquals($consecutiveUpdateMessageParams, $parameters);
    }
}
