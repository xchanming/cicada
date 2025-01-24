<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\MediaSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\ProductSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Field\FieldSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Exception\InvalidMediaUrlException;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Media\File\FileSaver;
use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class ProductSerializerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $visibilityRepository;

    private EntityRepository $salesChannelRepository;

    private EntityRepository $productMediaRepository;

    private EntityRepository $productConfiguratorSettingRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->visibilityRepository = static::getContainer()->get('product_visibility.repository');
        $this->salesChannelRepository = static::getContainer()->get('sales_channel.repository');
        $this->productMediaRepository = static::getContainer()->get('product_media.repository');
        $this->productConfiguratorSettingRepository = static::getContainer()->get('product_configurator_setting.repository');
    }

    public function testOnlySupportsProduct(): void
    {
        $serializer = new ProductSerializer(
            $this->visibilityRepository,
            $this->salesChannelRepository,
            $this->productMediaRepository,
            $this->productConfiguratorSettingRepository
        );

        static::assertTrue($serializer->supports('product'), 'should support product');

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();
            if ($entity !== 'product') {
                static::assertFalse(
                    $serializer->supports($definition->getEntityName()),
                    ProductSerializer::class . ' should not support ' . $entity
                );
            }
        }
    }

    public function testProductSerialize(): void
    {
        $product = $this->getProduct();

        $productDefinition = static::getContainer()->get(ProductDefinition::class);

        $serializer = new ProductSerializer(
            $this->visibilityRepository,
            $this->salesChannelRepository,
            $this->productMediaRepository,
            $this->productConfiguratorSettingRepository
        );
        $serializer->setRegistry(static::getContainer()->get(SerializerRegistry::class));

        $serialized = iterator_to_array($serializer->serialize(new Config([], [], []), $productDefinition, $product));

        static::assertNotEmpty($serialized);

        static::assertSame($product->getId(), $serialized['id']);
        static::assertSame($product->getTranslations()?->first()?->getName(), $serialized['translations']['DEFAULT']['name']);
        static::assertSame((string) $product->getStock(), $serialized['stock']);
        static::assertSame($product->getProductNumber(), $serialized['productNumber']);
        static::assertSame('1', $serialized['active']);
        static::assertStringContainsString('cicada-logo.png', $serialized['cover']['media']['url']);
        static::assertStringContainsString('cicada-icon.png', $serialized['media']);
        static::assertStringContainsString('cicada-background.png', $serialized['media']);
        static::assertStringNotContainsString('cicada-logo.png', $serialized['media']);

        $iterator = $serializer->deserialize(new Config([], [], []), $productDefinition, $serialized);
        static::assertInstanceOf(\Traversable::class, $iterator);
        $deserialized = iterator_to_array($iterator);

        static::assertSame($product->getId(), $deserialized['id']);
        $translations = $product->getTranslations();
        static::assertNotNull($translations);

        $first = $translations->first();
        static::assertNotNull($first);
        static::assertSame($first->getName(), $deserialized['translations'][Defaults::LANGUAGE_SYSTEM]['name']);
        static::assertSame($product->getStock(), $deserialized['stock']);
        static::assertSame($product->getProductNumber(), $deserialized['productNumber']);
        static::assertSame($product->getActive(), $deserialized['active']);
    }

    public function testSupportsOnlyProduct(): void
    {
        $serializer = new ProductSerializer(
            $this->visibilityRepository,
            $this->salesChannelRepository,
            $this->productMediaRepository,
            $this->productConfiguratorSettingRepository
        );

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            if ($entity === ProductDefinition::ENTITY_NAME) {
                static::assertTrue($serializer->supports($entity));
            } else {
                static::assertFalse(
                    $serializer->supports($entity),
                    ProductDefinition::class . ' should not support ' . $entity
                );
            }
        }
    }

    public function testDeserializeProductMedia(): void
    {
        $product = $this->getProduct();

        $mediaService = $this->createMock(MediaService::class);
        $expectedMediaFile = new MediaFile(
            '/tmp/foo/bar/cicada-logo.png',
            'image/png',
            'png',
            1000,
            'bc0d90db4dd806bd671ae9f7fabc5796'
        );
        $mediaService->expects(static::any())
            ->method('fetchFile')
            ->willReturnCallback(function (Request $request) use ($expectedMediaFile): MediaFile {
                if ($request->get('url') === 'http://172.16.11.80/cicada-logo.png') {
                    return $expectedMediaFile;
                }

                return new MediaFile(
                    '/tmp/foo/bar/baz',
                    'image/png',
                    'png',
                    1000,
                    Uuid::randomHex()
                );
            });

        $fileSaver = $this->createMock(FileSaver::class);
        $mediaSerializer = new MediaSerializer(
            $mediaService,
            $fileSaver,
            static::getContainer()->get('media_folder.repository'),
            static::getContainer()->get('media.repository')
        );
        $mediaSerializer->setRegistry(static::getContainer()->get(SerializerRegistry::class));

        $serializerRegistry = $this->createMock(SerializerRegistry::class);
        $serializerRegistry->expects(static::any())
            ->method('getEntity')
            ->willReturn($mediaSerializer);
        $serializerRegistry->expects(static::any())
            ->method('getFieldSerializer')
            ->willReturn(new FieldSerializer());

        $record = [
            'id' => $product->getId(),
            'media' => 'http://172.16.11.80/cicada-logo.png|http://172.16.11.80/cicada-logo2.png',
        ];

        $productDefinition = static::getContainer()->get(ProductDefinition::class);

        $serializer = new ProductSerializer(
            $this->visibilityRepository,
            $this->salesChannelRepository,
            $this->productMediaRepository,
            $this->productConfiguratorSettingRepository
        );
        $serializer->setRegistry($serializerRegistry);

        $result = $serializer->deserialize(new Config([], [], []), $productDefinition, $record);
        $result = \is_array($result) ? $result : iterator_to_array($result);

        static::assertEquals($product->getMedia()?->first()?->getId(), $result['media'][0]['id']);
        static::assertEquals($product->getMedia()?->first()?->getMedia()?->getId(), $result['media'][0]['media']['id']);
        static::assertArrayNotHasKey('url', $result['media'][0]['media']);

        static::assertArrayNotHasKey('id', $result['media'][1]);
    }

    public function testDeserializeProductMediaWithInvalidUrl(): void
    {
        $record = [
            'media' => 'foo',
        ];

        $productDefinition = static::getContainer()->get(ProductDefinition::class);

        $serializer = new ProductSerializer(
            $this->visibilityRepository,
            $this->salesChannelRepository,
            $this->productMediaRepository,
            $this->productConfiguratorSettingRepository
        );
        $serializer->setRegistry(static::getContainer()->get(SerializerRegistry::class));

        $result = $serializer->deserialize(new Config([], [], []), $productDefinition, $record);
        $result = \is_array($result) ? $result : iterator_to_array($result);

        static::assertArrayHasKey('_error', $result);
        static::assertInstanceOf(InvalidMediaUrlException::class, $result['_error']);
    }

    private function getProduct(): ProductEntity
    {
        $productId = Uuid::randomHex();

        $product = [
            'id' => $productId,
            'stock' => 101,
            'productNumber' => 'P101',
            'active' => true,
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'test product',
                ],
            ],
            'tax' => [
                'name' => '19%',
                'taxRate' => 19.0,
            ],
            'price' => [
                Defaults::CURRENCY => [
                    'gross' => 1.111,
                    'net' => 1.011,
                    'linked' => true,
                    'currencyId' => Defaults::CURRENCY,
                    'listPrice' => [
                        'gross' => 1.111,
                        'net' => 1.011,
                        'linked' => false,
                        'currencyId' => Defaults::CURRENCY,
                    ],
                ],
            ],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
            'categories' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => 'test category',
                ],
            ],
            'cover' => [
                'id' => Uuid::randomHex(),
                'position' => 0,
                'media' => [
                    'id' => Uuid::randomHex(),
                    'fileName' => 'cicada-logo',
                    'path' => 'cicada-logo.png',
                    'fileExtension' => 'png',
                    'mimeType' => 'image/png',
                    'metaData' => [
                        'hash' => 'bc0d90db4dd806bd671ae9f7fabc5796',
                    ],
                ],
            ],
            'media' => [
                [
                    'id' => Uuid::randomHex(),
                    'position' => 1,
                    'media' => [
                        'id' => Uuid::randomHex(),
                        'fileName' => 'cicada-icon',
                        'path' => 'cicada-icon.png',
                        'fileExtension' => 'png',
                        'mimeType' => 'image/png',
                    ],
                ],
                [
                    'id' => Uuid::randomHex(),
                    'position' => 2,
                    'media' => [
                        'id' => Uuid::randomHex(),
                        'fileName' => 'cicada-background',
                        'path' => 'cicada-background.png',
                        'fileExtension' => 'png',
                        'mimeType' => 'image/png',
                    ],
                ],
            ],
        ];

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $productRepository->create([$product], Context::createDefaultContext());

        $criteria = new Criteria();
        $criteria->addAssociation('translations');
        $criteria->addAssociation('visibilities');
        $criteria->addAssociation('tax');
        $criteria->addAssociation('categories');
        $criteria->addAssociation('cover.media');
        $criteria->addAssociation('media.media');
        $criteria->getAssociation('media')->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $product = $productRepository->search($criteria, Context::createDefaultContext())->first();
        static::assertInstanceOf(ProductEntity::class, $product);

        return $product;
    }
}
