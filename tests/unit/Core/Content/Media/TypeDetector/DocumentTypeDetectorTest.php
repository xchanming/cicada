<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\TypeDetector;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\DocumentType;
use Cicada\Core\Content\Media\MediaType\ImageType;
use Cicada\Core\Content\Media\TypeDetector\DocumentTypeDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DocumentTypeDetector::class)]
class DocumentTypeDetectorTest extends TestCase
{
    public function testDetectGif(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/logo.gif'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectWebp(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.vp8x.webp'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectAvif(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.avif'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectSvg(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/logo-version-professionalplus.svg'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectJpg(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada.jpg'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectPng(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.png'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectDoc(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/reader.doc'),
            null
        );

        static::assertInstanceOf(DocumentType::class, $type);
    }

    public function testDetectDocx(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/reader.docx'),
            null
        );

        static::assertInstanceOf(DocumentType::class, $type);
    }

    public function testDetectPdf(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.pdf'),
            null
        );

        static::assertInstanceOf(DocumentType::class, $type);
    }

    public function testDetectPdfDoesNotOverwrite(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.pdf'),
            new ImageType()
        );

        static::assertInstanceOf(ImageType::class, $type);
    }

    public function testDetectAvi(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.avi'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectMov(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.mov'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectMp4(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.mp4'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectWebm(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.webm'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectIso(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/test.iso'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectMp3(): void
    {
        $type = $this->getDocumentTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/file_example.mp3'),
            null
        );

        static::assertNull($type);
    }

    private function getDocumentTypeDetector(): DocumentTypeDetector
    {
        return new DocumentTypeDetector();
    }

    private function createMediaFile(string $filePath): MediaFile
    {
        static::assertIsString($mimeContentType = mime_content_type($filePath));
        static::assertIsInt($filesize = filesize($filePath));

        return new MediaFile(
            $filePath,
            $mimeContentType,
            pathinfo($filePath, \PATHINFO_EXTENSION),
            $filesize
        );
    }
}
