<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Metadata;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\DocumentType;
use Cicada\Core\Content\Media\MediaType\ImageType;
use Cicada\Core\Content\Media\Metadata\MetadataLoader;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class MetadataLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testJpg(): void
    {
        $result = $this
            ->getMetadataLoader()
            ->loadFromFile($this->createMediaFile(__DIR__ . '/../fixtures/cicada.jpg'), new ImageType());

        $expected = [
            'type' => \IMAGETYPE_JPEG,
            'width' => 1530,
            'height' => 1021,
        ];

        static::assertIsArray($result);
        static::assertEquals($expected, $result);
    }

    public function testGif(): void
    {
        $result = $this
            ->getMetadataLoader()
            ->loadFromFile($this->createMediaFile(__DIR__ . '/../fixtures/logo.gif'), new ImageType());

        $expected = [
            'type' => \IMAGETYPE_GIF,
            'width' => 142,
            'height' => 37,
        ];

        static::assertIsArray($result);
        static::assertEquals($expected, $result);
    }

    public function testPng(): void
    {
        $result = $this
            ->getMetadataLoader()
            ->loadFromFile($this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.png'), new ImageType());

        $expected = [
            'type' => \IMAGETYPE_PNG,
            'width' => 499,
            'height' => 266,
        ];

        static::assertIsArray($result);
        static::assertEquals($expected, $result);
    }

    public function testSvg(): void
    {
        $result = $this
            ->getMetadataLoader()
            ->loadFromFile($this->createMediaFile(__DIR__ . '/../fixtures/logo-version-professionalplus.svg'), new ImageType());

        static::assertNull($result);
    }

    public function testPdf(): void
    {
        $result = $this
            ->getMetadataLoader()
            ->loadFromFile($this->createMediaFile(__DIR__ . '/../fixtures/small.pdf'), new DocumentType());

        static::assertNull($result);
    }

    private function getMetadataLoader(): MetadataLoader
    {
        return static::getContainer()
            ->get(MetadataLoader::class);
    }

    private function createMediaFile(string $filePath): MediaFile
    {
        $mimeType = mime_content_type($filePath);
        static::assertIsString($mimeType);

        $fileSize = filesize($filePath);
        static::assertIsInt($fileSize);

        return new MediaFile(
            $filePath,
            $mimeType,
            pathinfo($filePath, \PATHINFO_EXTENSION),
            $fileSize
        );
    }
}
