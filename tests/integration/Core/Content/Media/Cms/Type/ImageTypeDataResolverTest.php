<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Cms\Type;

use Cicada\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Cicada\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Cicada\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Cicada\Core\Content\Cms\SalesChannel\Struct\ImageStruct;
use Cicada\Core\Content\Media\Cms\DefaultMediaResolver;
use Cicada\Core\Content\Media\Cms\ImageCmsElementResolver;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Cicada\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ImageTypeDataResolverTest extends TestCase
{
    use IntegrationTestBehaviour;

    private ImageCmsElementResolver $imageResolver;

    private FilesystemOperator $publicFilesystem;

    protected function setUp(): void
    {
        $this->publicFilesystem = $this->getPublicFilesystem();
        $this->imageResolver = new ImageCmsElementResolver(new DefaultMediaResolver($this->publicFilesystem));
    }

    public function testType(): void
    {
        static::assertSame('image', $this->imageResolver->getType());
    }

    public function testCollectWithEmptyConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig([]);
        $slot->setFieldConfig(new FieldConfigCollection());

        $criteriaCollection = $this->imageResolver->collect($slot, $resolverContext);

        static::assertNull($criteriaCollection);
    }

    public function testCollectWithMediaId(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_STATIC, 'media123'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setFieldConfig($fieldConfig);

        $criteriaCollection = $this->imageResolver->collect($slot, $resolverContext);

        static::assertNotNull($criteriaCollection);
        static::assertCount(1, iterator_to_array($criteriaCollection));

        $expectedCriteria = new Criteria(['media123']);

        $mediaCriteria = $criteriaCollection->all()[MediaDefinition::class]['media_' . $slot->getUniqueIdentifier()];

        static::assertEquals($expectedCriteria, $mediaCriteria);
    }

    public function testEnrichWithEmptyConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig([]);
        $slot->setFieldConfig(new FieldConfigCollection());

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertEmpty($imageStruct->getUrl());
        static::assertEmpty($imageStruct->getMedia());
        static::assertEmpty($imageStruct->getMediaId());
    }

    public function testEnrichWithUrlOnly(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('url', FieldConfig::SOURCE_STATIC, 'http://cicada.com/image.jpg'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig(['url' => 'http://cicada.com/image.jpg']);
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertSame('http://cicada.com/image.jpg', $imageStruct->getUrl());
        static::assertEmpty($imageStruct->getMedia());
        static::assertEmpty($imageStruct->getMediaId());
    }

    public function testEnrichWithUrlAndNewTabOnly(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('url', FieldConfig::SOURCE_STATIC, 'http://cicada.com/image.jpg'));
        $fieldConfig->add(new FieldConfig('newTab', FieldConfig::SOURCE_STATIC, true));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig(['url' => 'http://cicada.com/image.jpg']);
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertSame('http://cicada.com/image.jpg', $imageStruct->getUrl());
        static::assertTrue($imageStruct->getNewTab());
        static::assertEmpty($imageStruct->getMedia());
        static::assertEmpty($imageStruct->getMediaId());
    }

    public function testEnrichWithMediaOnly(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $media = new MediaEntity();
        $media->setUniqueIdentifier('media123');

        $mediaSearchResult = new EntitySearchResult(
            'media',
            1,
            new MediaCollection([$media]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $result = new ElementDataCollection();
        $result->add('media_id', $mediaSearchResult);

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_STATIC, 'media123'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig(['media' => 'media123', 'source' => FieldConfig::SOURCE_STATIC]);
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertEmpty($imageStruct->getUrl());
        static::assertInstanceOf(MediaEntity::class, $imageStruct->getMedia());
        static::assertSame('media123', $imageStruct->getMediaId());
        static::assertSame($media, $imageStruct->getMedia());
    }

    public function testEnrichWithMediaAndUrl(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $media = new MediaEntity();
        $media->setUniqueIdentifier('media123');

        $mediaSearchResult = new EntitySearchResult(
            'media',
            1,
            new MediaCollection([$media]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $result = new ElementDataCollection();
        $result->add('media_id', $mediaSearchResult);

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_STATIC, 'media123'));
        $fieldConfig->add(new FieldConfig('url', FieldConfig::SOURCE_STATIC, 'http://cicada.com/image.jpg'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig(['mediaId' => 'media123', 'url' => 'http://cicada.com/image.jpg']);
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertSame('http://cicada.com/image.jpg', $imageStruct->getUrl());
        static::assertInstanceOf(MediaEntity::class, $imageStruct->getMedia());
        static::assertSame('media123', $imageStruct->getMediaId());
        static::assertSame($media, $imageStruct->getMedia());
    }

    public function testEnrichWithMissingMediaId(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $media = new MediaEntity();
        $media->setUniqueIdentifier('media123');

        $mediaSearchResult = new EntitySearchResult(
            'media',
            0,
            new MediaCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $result = new ElementDataCollection();
        $result->add('media', $mediaSearchResult);

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_STATIC, 'media123'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig(['mediaId' => 'media123']);
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertEmpty($imageStruct->getUrl());
        static::assertSame('media123', $imageStruct->getMediaId());
        static::assertEmpty($imageStruct->getMedia());
    }

    public function testEnrichWithDefaultConfig(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());
        $result = new ElementDataCollection();

        $this->publicFilesystem->write('/bundles/core/assets/default/cms/cicada.jpg', '');

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_DEFAULT, 'bundles/core/assets/default/cms/cicada.jpg'));

        $slot = new CmsSlotEntity();
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        $media = $imageStruct->getMedia();
        static::assertInstanceOf(MediaEntity::class, $media);
        static::assertEquals('cicada', $media->getFileName());
        static::assertEquals('image/jpeg', $media->getMimeType());
        static::assertEquals('jpg', $media->getFileExtension());
    }

    public function testMediaWithRemote(): void
    {
        $resolverContext = new ResolverContext($this->createMock(SalesChannelContext::class), new Request());

        $media = new MediaEntity();
        $media->setUniqueIdentifier('media123');

        $mediaSearchResult = new EntitySearchResult(
            'media',
            1,
            new MediaCollection([$media]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $result = new ElementDataCollection();
        $result->add('media_id', $mediaSearchResult);

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_STATIC, 'media123'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setConfig(json_decode(json_encode($fieldConfig, \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR));
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertEmpty($imageStruct->getUrl());
        static::assertSame('media123', $imageStruct->getMediaId());
        static::assertSame($media, $imageStruct->getMedia());
    }

    public function testMediaWithLocal(): void
    {
        $media = new MediaEntity();
        $media->setUniqueIdentifier('media123');

        $productMedia = new ProductMediaEntity();
        $productMedia->setMedia($media);

        $product = new ProductEntity();
        $product->setCover($productMedia);

        $resolverContext = new EntityResolverContext($this->createMock(SalesChannelContext::class), new Request(), $this->createMock(ProductDefinition::class), $product);

        $mediaSearchResult = new EntitySearchResult(
            'media',
            0,
            new MediaCollection(),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $result = new ElementDataCollection();
        $result->add('media_id', $mediaSearchResult);

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('media', FieldConfig::SOURCE_MAPPED, 'cover.media'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertEmpty($imageStruct->getUrl());
        static::assertSame('media123', $imageStruct->getMediaId());
        static::assertSame($media, $imageStruct->getMedia());
    }

    public function testUrlWithLocal(): void
    {
        $manufacturer = new ProductManufacturerEntity();
        $manufacturer->setLink('http://cicada.com');

        $product = new ProductEntity();
        $product->setManufacturer($manufacturer);

        $resolverContext = new EntityResolverContext($this->createMock(SalesChannelContext::class), new Request(), $this->createMock(ProductDefinition::class), $product);

        $result = new ElementDataCollection();

        $fieldConfig = new FieldConfigCollection();
        $fieldConfig->add(new FieldConfig('url', FieldConfig::SOURCE_MAPPED, 'manufacturer.link'));

        $slot = new CmsSlotEntity();
        $slot->setUniqueIdentifier('id');
        $slot->setType('image');
        $slot->setFieldConfig($fieldConfig);

        $this->imageResolver->enrich($slot, $resolverContext, $result);

        $imageStruct = $slot->getData();
        static::assertInstanceOf(ImageStruct::class, $imageStruct);
        static::assertSame($manufacturer->getLink(), $imageStruct->getUrl());
        static::assertEmpty($imageStruct->getMediaId());
        static::assertEmpty($imageStruct->getMedia());
    }
}
