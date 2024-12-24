<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\MediaSerializer;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\Entity\MediaSerializerSubscriber;
use Cicada\Core\Content\ImportExport\DataAbstractionLayer\Serializer\SerializerRegistry;
use Cicada\Core\Content\ImportExport\Exception\InvalidMediaUrlException;
use Cicada\Core\Content\ImportExport\Exception\MediaDownloadException;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Media\File\FileSaver;
use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('services-settings')]
class MediaSerializerTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testDeserializeDownloadsAndPersistsMedia(): void
    {
        $context = Context::createDefaultContext();
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);
        $mediaDefinition = static::getContainer()->get(MediaDefinition::class);

        $mediaService = $this->createMock(MediaService::class);
        $fileSaver = $this->createMock(FileSaver::class);

        $mediaFolderRepository = $this->createMock(EntityRepository::class);
        $mediaRepository = $this->createMock(EntityRepository::class);

        $mediaSerializer = new MediaSerializer($mediaService, $fileSaver, $mediaFolderRepository, $mediaRepository);
        $mediaSerializer->setRegistry($serializerRegistry);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new MediaSerializerSubscriber($mediaSerializer));

        $mediaId = Uuid::randomHex();
        $expectedDestination = 'cicada-logo';
        $record = [
            'id' => $mediaId,
            'title' => 'Logo',
            'url' => 'http://172.16.11.80/cicada-logo.png',
            'mediaFolderId' => Uuid::randomHex(),
        ];

        $expectedMediaFile = new MediaFile(
            '/tmp/foo/bar/baz',
            'image/png',
            'png',
            1337
        );
        $mediaService->expects(static::once())
            ->method('fetchFile')
            ->willReturn($expectedMediaFile);

        $fileSaver->expects(static::once())
            ->method('persistFileToMedia')
            ->willReturnCallback(function (MediaFile $m, string $dest, string $id) use ($expectedMediaFile, $expectedDestination, $mediaId): void {
                $this->assertSame($expectedMediaFile, $m);
                $this->assertSame($expectedDestination, $dest);
                $this->assertSame($mediaId, $id);
            });

        $result = $mediaSerializer->deserialize(new Config([], [], []), $mediaDefinition, $record);
        $result = \is_array($result) ? $result : iterator_to_array($result);

        $writtenResult = new EntityWriteResult($mediaId, $result, 'media', 'insert');
        $writtenEvent = new EntityWrittenEvent('media', [$writtenResult], $context);
        $eventDispatcher->dispatch($writtenEvent, 'media.written');
    }

    public function testExistingMediaWithSameUrlDoesNotDownload(): void
    {
        $context = Context::createDefaultContext();
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);
        $mediaDefinition = static::getContainer()->get(MediaDefinition::class);

        $mediaService = $this->createMock(MediaService::class);
        $fileSaver = $this->createMock(FileSaver::class);

        $mediaFolderRepository = $this->createMock(EntityRepository::class);
        $mediaRepository = $this->createMock(EntityRepository::class);

        $mediaSerializer = new MediaSerializer($mediaService, $fileSaver, $mediaFolderRepository, $mediaRepository);
        $mediaSerializer->setRegistry($serializerRegistry);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new MediaSerializerSubscriber($mediaSerializer));

        $mediaId = Uuid::randomHex();
        $record = [
            'id' => $mediaId,
            'url' => 'http://172.16.11.80/cicada-logo.png',
        ];

        $mediaEntity = new MediaEntity();
        $mediaEntity->assign($record);

        $record['mediaFolderId'] = Uuid::randomHex();
        $record['translations'] = [
            Defaults::LANGUAGE_SYSTEM => [
                'title' => 'Logo',
                'alt' => 'Logo description',
            ],
        ];

        $mediaService->expects(static::never())
            ->method('fetchFile');

        $fileSaver->expects(static::never())
            ->method('persistFileToMedia');

        $searchResult = new EntitySearchResult('media', 1, new MediaCollection([$mediaEntity]), null, new Criteria(), $context);
        $mediaRepository->method('search')->willReturn($searchResult);

        $result = $mediaSerializer->deserialize(new Config([], [], []), $mediaDefinition, $record);
        $result = \is_array($result) ? $result : iterator_to_array($result);

        static::assertArrayNotHasKey('url', $result);

        $expected = $record;
        unset($expected['url']);

        // other properties are written
        static::assertEquals($expected, $result);

        $writtenResult = new EntityWriteResult($mediaId, $result, 'media', 'insert');
        $writtenEvent = new EntityWrittenEvent('media', [$writtenResult], $context);
        $eventDispatcher->dispatch($writtenEvent, 'media.written');
    }

    public function testOnlyUrl(): void
    {
        $context = Context::createDefaultContext();
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);
        $mediaDefinition = static::getContainer()->get(MediaDefinition::class);

        $mediaService = $this->createMock(MediaService::class);
        $fileSaver = $this->createMock(FileSaver::class);

        $mediaFolderRepository = static::getContainer()->get('media_folder.repository');
        $mediaRepository = $this->createMock(EntityRepository::class);

        $mediaSerializer = new MediaSerializer($mediaService, $fileSaver, $mediaFolderRepository, $mediaRepository);
        $mediaSerializer->setRegistry($serializerRegistry);

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addSubscriber(new MediaSerializerSubscriber($mediaSerializer));

        $expectedDestination = 'cicada-logo';
        $record = [
            'url' => 'http://172.16.11.80/cicada-logo.png',
        ];

        $expectedMediaFile = new MediaFile(
            '/tmp/foo/bar/baz',
            'image/png',
            'png',
            1337
        );
        $mediaService->expects(static::once())
            ->method('fetchFile')
            ->willReturn($expectedMediaFile);

        $fileSaver->expects(static::once())
            ->method('persistFileToMedia')
            ->willReturnCallback(function (MediaFile $m, string $dest) use ($expectedMediaFile, $expectedDestination): void {
                $this->assertSame($expectedMediaFile, $m);
                $this->assertSame($expectedDestination, $dest);
            });

        $result = $mediaSerializer->deserialize(new Config([], [], []), $mediaDefinition, $record);
        $result = \is_array($result) ? $result : iterator_to_array($result);

        $writtenResult = new EntityWriteResult($result['id'], $result, 'media', 'insert');
        $writtenEvent = new EntityWrittenEvent('media', [$writtenResult], $context);
        $eventDispatcher->dispatch($writtenEvent, 'media.written');
    }

    public function testInvalidUrl(): void
    {
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);
        $mediaDefinition = static::getContainer()->get(MediaDefinition::class);

        $mediaService = $this->createMock(MediaService::class);
        $fileSaver = $this->createMock(FileSaver::class);

        $mediaFolderRepository = static::getContainer()->get('media_folder.repository');
        $mediaRepository = $this->createMock(EntityRepository::class);

        $mediaSerializer = new MediaSerializer($mediaService, $fileSaver, $mediaFolderRepository, $mediaRepository);
        $mediaSerializer->setRegistry($serializerRegistry);

        $actual = $mediaSerializer->deserialize(new Config([], [], []), $mediaDefinition, ['url' => 'invalid']);
        $actual = \is_array($actual) ? $actual : iterator_to_array($actual);

        // only the error should be in the result
        static::assertCount(1, $actual);
        static::assertInstanceOf(InvalidMediaUrlException::class, $actual['_error']);
    }

    public function testEmpty(): void
    {
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);
        $mediaDefinition = static::getContainer()->get(MediaDefinition::class);

        $mediaService = $this->createMock(MediaService::class);
        $fileSaver = $this->createMock(FileSaver::class);

        $mediaFolderRepository = static::getContainer()->get('media_folder.repository');
        $mediaRepository = $this->createMock(EntityRepository::class);

        $mediaSerializer = new MediaSerializer($mediaService, $fileSaver, $mediaFolderRepository, $mediaRepository);
        $mediaSerializer->setRegistry($serializerRegistry);
        $config = new Config([], [], []);

        $actual = $mediaSerializer->deserialize($config, $mediaDefinition, []);
        // should not contain url
        static::assertEmpty($actual);
    }

    public function testFailedDownload(): void
    {
        $serializerRegistry = static::getContainer()->get(SerializerRegistry::class);
        $mediaDefinition = static::getContainer()->get(MediaDefinition::class);

        $mediaService = $this->createMock(MediaService::class);
        $fileSaver = $this->createMock(FileSaver::class);

        $mediaFolderRepository = static::getContainer()->get('media_folder.repository');
        $mediaRepository = $this->createMock(EntityRepository::class);

        $mediaSerializer = new MediaSerializer($mediaService, $fileSaver, $mediaFolderRepository, $mediaRepository);
        $mediaSerializer->setRegistry($serializerRegistry);

        $record = [
            'url' => 'http://localhost/some/path/to/non/existing/image.png',
        ];

        $actual = $mediaSerializer->deserialize(new Config([], [], []), $mediaDefinition, $record);
        $actual = \is_array($actual) ? $actual : iterator_to_array($actual);
        static::assertInstanceOf(MediaDownloadException::class, $actual['_error']);
    }

    public function testSupportsOnlyMedia(): void
    {
        $serializer = new MediaSerializer(
            $this->createMock(MediaService::class),
            $this->createMock(FileSaver::class),
            static::getContainer()->get('media_folder.repository'),
            static::getContainer()->get('media.repository')
        );

        $definitionRegistry = static::getContainer()->get(DefinitionInstanceRegistry::class);
        foreach ($definitionRegistry->getDefinitions() as $definition) {
            $entity = $definition->getEntityName();

            if ($entity === MediaDefinition::ENTITY_NAME) {
                static::assertTrue($serializer->supports($entity));
            } else {
                static::assertFalse(
                    $serializer->supports($entity),
                    MediaSerializer::class . ' should not support ' . $entity
                );
            }
        }
    }
}
