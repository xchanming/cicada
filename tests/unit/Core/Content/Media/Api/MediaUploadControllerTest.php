<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Api;

use Cicada\Core\Content\Media\Api\MediaUploadController;
use Cicada\Core\Content\Media\File\FileNameProvider;
use Cicada\Core\Content\Media\File\FileSaver;
use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Framework\Api\Response\ResponseFactoryInterface;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(MediaUploadController::class)]
class MediaUploadControllerTest extends TestCase
{
    private FileSaver&MockObject $fileSaver;

    private MediaService&MockObject $mediaService;

    private FileNameProvider&MockObject $fileNameProvider;

    private ResponseFactoryInterface&MockObject $responseFactory;

    protected function setUp(): void
    {
        $this->fileSaver = $this->createMock(FileSaver::class);
        $this->mediaService = $this->createMock(MediaService::class);
        $this->fileNameProvider = $this->createMock(FileNameProvider::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
    }

    public function testRemoveNonPrintingCharactersInFileNameBeforeUpload(): void
    {
        $invalidFileName = 'file­name.png';
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $request = new Request(['fileName' => $invalidFileName]);

        $uploadFile = new MediaFile(
            '/tmp/foo/bar/baz',
            'image/png',
            'png',
            1000,
            Uuid::randomHex()
        );

        $this->mediaService->expects(static::once())
            ->method('fetchFile')
            ->willReturn($uploadFile);

        $this->fileSaver->expects(static::once())
            ->method('persistFileToMedia')
            ->with($uploadFile, 'filename.png', $mediaId, $context);

        $mediaUploadController = new MediaUploadController(
            $this->mediaService,
            $this->fileSaver,
            $this->fileNameProvider,
            new MediaDefinition(),
            new EventDispatcher()
        );

        $mediaUploadController->upload($request, $mediaId, $context, $this->responseFactory);
    }

    public function testRemoveNonPrintingCharactersInFileNameBeforeRename(): void
    {
        $invalidFileName = 'file­name.png';
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $request = new Request([], ['fileName' => $invalidFileName]);

        $this->fileSaver->expects(static::once())
            ->method('renameMedia')
            ->with($mediaId, 'filename.png', $context);

        $mediaUploadController = new MediaUploadController(
            $this->mediaService,
            $this->fileSaver,
            $this->fileNameProvider,
            new MediaDefinition(),
            new EventDispatcher()
        );

        $mediaUploadController->renameMediaFile($request, $mediaId, $context, $this->responseFactory);
    }

    public function testRemoveNonPrintingCharactersInFileNameBeforeProvideName(): void
    {
        $invalidFileName = 'file­name.png';
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $request = new Request([
            'fileName' => $invalidFileName,
            'extension' => 'jpg',
            'mediaId' => $mediaId,
        ]);

        $this->fileNameProvider->expects(static::once())
            ->method('provide')
            ->with('filename.png', 'jpg', $mediaId, $context);

        $mediaUploadController = new MediaUploadController(
            $this->mediaService,
            $this->fileSaver,
            $this->fileNameProvider,
            new MediaDefinition(),
            new EventDispatcher()
        );

        $mediaUploadController->provideName($request, $context);
    }
}
