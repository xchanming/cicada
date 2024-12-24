<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media;

use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailCollection;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Test\Media\MediaFixtures;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MediaEntityTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MediaFixtures;

    /**
     * @var EntityRepository<MediaCollection>
     */
    private EntityRepository $repository;

    private Context $context;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('media.repository');
        $this->context = Context::createDefaultContext();
    }

    public function testWriteReadMinimalFields(): void
    {
        $media = $this->getEmptyMedia();

        $criteria = $this->getIdCriteria($media->getId());
        $result = $this->repository->search($criteria, $this->context);
        $media = $result->getEntities()->first();

        static::assertInstanceOf(MediaEntity::class, $media);
        static::assertEquals($media->getId(), $media->getId());
    }

    public function testThumbnailsAreConvertedToStructWhenFetchedFromDb(): void
    {
        $this->setFixtureContext($this->context);
        $media = $this->getMediaWithThumbnail();

        $criteria = $this->getIdCriteria($media->getId());
        $searchResult = $this->repository->search($criteria, $this->context);
        $fetchedMedia = $searchResult->getEntities()->get($media->getId());

        static::assertInstanceOf(MediaEntity::class, $fetchedMedia);
        static::assertInstanceOf(MediaThumbnailCollection::class, $fetchedMedia->getThumbnails());

        $persistedThumbnail = $fetchedMedia->getThumbnails()->first();
        static::assertInstanceOf(MediaThumbnailEntity::class, $persistedThumbnail);
        static::assertEquals(200, $persistedThumbnail->getWidth());
        static::assertEquals(200, $persistedThumbnail->getHeight());
    }

    public function testDeleteMediaWithTags(): void
    {
        $media = $this->getEmptyMedia();

        $this->repository->update([
            [
                'id' => $media->getId(),
                'tags' => [['name' => 'test tag']],
            ],
        ], $this->context);

        $this->repository->delete([['id' => $media->getId()]], $this->context);
    }

    private function getIdCriteria(string $mediaId): Criteria
    {
        $criteria = new Criteria();
        $criteria->setOffset(0);
        $criteria->setLimit(1);
        $criteria->addFilter(new EqualsFilter('media.id', $mediaId));

        return $criteria;
    }
}
