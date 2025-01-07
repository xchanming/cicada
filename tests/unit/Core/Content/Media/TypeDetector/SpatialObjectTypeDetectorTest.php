<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\TypeDetector;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaType\ImageType;
use Cicada\Core\Content\Media\MediaType\SpatialObjectType;
use Cicada\Core\Content\Media\TypeDetector\SpatialObjectTypeDetector;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(SpatialObjectTypeDetector::class)]
class SpatialObjectTypeDetectorTest extends TestCase
{
    /**
     * @var MediaFile&MockObject
     */
    private MediaFile $mediaFile;

    protected function setUp(): void
    {
        $this->mediaFile = $this->createMock(MediaFile::class);
    }

    public function testDetectWithExtensionGlbWillReturnSpatialObjectType(): void
    {
        $this->mediaFile->method('getFileExtension')->willReturn('glb');
        $detectedType = (new SpatialObjectTypeDetector())->detect($this->mediaFile, null);
        static::assertInstanceOf(SpatialObjectType::class, $detectedType);
    }

    public function testDetectWithPreviouslyDetectedTypeButExtensionGlbWillReturnOriginalType(): void
    {
        $this->mediaFile->method('getFileExtension')->willReturn('glb');
        $detectedType = (new SpatialObjectTypeDetector())->detect($this->mediaFile, new ImageType());
        static::assertInstanceOf(ImageType::class, $detectedType);
    }

    public function testDetectWithPreviouslyDetectedTypeAndNot3dFileExtensionWillReturnOriginalType(): void
    {
        $this->mediaFile->method('getFileExtension')->willReturn('png');
        $detectedType = (new SpatialObjectTypeDetector())->detect($this->mediaFile, new ImageType());
        static::assertInstanceOf(ImageType::class, $detectedType);
    }
}
