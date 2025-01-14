<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Aggregate\MediaFolderConfigurationThumbnailSize;

use Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration\MediaFolderConfigurationCollection;
use Cicada\Core\Content\Media\Aggregate\MediaFolderConfiguration\MediaFolderConfigurationEntity;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeCollection;
use Cicada\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MediaFolderConfigurationMediaThumbnailSizeTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCreateConfiguration(): void
    {
        $context = Context::createDefaultContext();
        /**
         * @var EntityRepository<MediaFolderConfigurationCollection> $repository
         */
        $repository = static::getContainer()->get('media_folder_configuration.repository');

        $configurationId = Uuid::randomHex();
        $sizeId = Uuid::randomHex();

        $repository->create([
            [
                'id' => $configurationId,
                'createThumbnails' => true,
                'mediaThumbnailSizes' => [
                    [
                        'id' => $sizeId,
                        'width' => 100,
                        'height' => 100,
                    ],
                ],
            ],
        ], $context);

        $criteria = new Criteria([$configurationId]);
        $criteria->addAssociation('mediaThumbnailSizes');

        $read = $repository->search($criteria, $context);
        $configuration = $read->get($configurationId);

        static::assertInstanceOf(MediaFolderConfigurationEntity::class, $configuration);
        $sizes = $configuration->getMediaThumbnailSizes();
        static::assertInstanceOf(MediaThumbnailSizeCollection::class, $sizes);
        static::assertEquals(1, $sizes->count());
        static::assertNotNull($sizes->get($sizeId));
    }

    public function testCreateThumbnailSize(): void
    {
        $context = Context::createDefaultContext();
        /**
         * @var EntityRepository<MediaThumbnailSizeCollection> $repository
         */
        $repository = static::getContainer()->get('media_thumbnail_size.repository');

        $sizeId = Uuid::randomHex();
        $confId = Uuid::randomHex();

        $repository->upsert([
            [
                'id' => $sizeId,
                'width' => 100,
                'height' => 100,
                'mediaFolderConfigurations' => [
                    [
                        'id' => $confId,
                        'createThumbnails' => true,
                    ],
                ],
            ],
        ], $context);

        $criteria = (new Criteria())
            ->addAssociation('mediaFolderConfigurations');

        $search = $repository->search($criteria, $context);

        $size = $search->getEntities()->get($sizeId);
        static::assertInstanceOf(MediaThumbnailSizeEntity::class, $size);
        $configurations = $size->getMediaFolderConfigurations();
        static::assertInstanceOf(MediaFolderConfigurationCollection::class, $configurations);
        static::assertEquals(1, $configurations->count());
        static::assertNotNull($configurations->get($confId));
    }
}
