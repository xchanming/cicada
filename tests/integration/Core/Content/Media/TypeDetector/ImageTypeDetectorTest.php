<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\TypeDetector;

use Cicada\Core\Content\Media\File\MediaFile;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Content\Media\MediaType\ImageType;
use Cicada\Core\Content\Media\MediaType\VideoType;
use Cicada\Core\Content\Media\TypeDetector\ImageTypeDetector;
use Cicada\Core\DevOps\Environment\EnvironmentHelper;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ImageTypeDetectorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testDetectGif(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/logo.gif'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
    }

    public function testDetectGifDoesNotOverwriteButAddsFlags(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/logo.gif'),
            new VideoType()
        );

        static::assertInstanceOf(VideoType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
    }

    public function testDetectAnimatedGif(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/animated.gif'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(2, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
        static::assertTrue($type->is(ImageType::ANIMATED));
    }

    #[Group('needsWebserver')]
    public function testDetectAnimatedGifFromUrl(): void
    {
        $publicPath = static::getContainer()->getParameter('kernel.project_dir') . '/public/animate.gif';
        \copy(
            __DIR__ . '/../fixtures/animated.gif',
            $publicPath
        );

        static::assertIsString(
            $appUrl = EnvironmentHelper::getVariable('APP_URL')
        );
        $webPath = rtrim($appUrl, '/') . '/animate.gif';

        $type = $this->getImageTypeDetector()->detect(
            new MediaFile(
                $webPath,
                'image/gif',
                'gif',
                1024
            ),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(2, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
        static::assertTrue($type->is(ImageType::ANIMATED));

        unlink($publicPath);
    }

    public function testDetectWebp(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.vp8x.webp'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
    }

    public function testDetectAnimatedWebp(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/animated.webp'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(2, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
        static::assertTrue($type->is(ImageType::ANIMATED));
    }

    #[Group('needsWebserver')]
    public function testDetectAnimatedWebpFromUrl(): void
    {
        $publicPath = static::getContainer()->getParameter('kernel.project_dir') . '/public/animate.webp';
        \copy(
            __DIR__ . '/../fixtures/animated.webp',
            $publicPath
        );

        static::assertIsString(
            $appUrl = EnvironmentHelper::getVariable('APP_URL')
        );
        $webPath = rtrim($appUrl, '/') . '/animate.webp';

        $type = $this->getImageTypeDetector()->detect(
            new MediaFile(
                $webPath,
                'image/webp',
                'webp',
                1024
            ),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(2, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
        static::assertTrue($type->is(ImageType::ANIMATED));

        unlink($publicPath);
    }

    public function testDetectAvif(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.avif'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
    }

    public function testDetectAnimatedAvif(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/animated.avif'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(2, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
        static::assertTrue($type->is(ImageType::ANIMATED));
    }

    #[Group('needsWebserver')]
    public function testDetectAnimatedAvifFromUrl(): void
    {
        $publicPath = static::getContainer()->getParameter('kernel.project_dir') . '/public/animate.avif';
        \copy(
            __DIR__ . '/../fixtures/animated.avif',
            $publicPath
        );

        static::assertIsString(
            $appUrl = EnvironmentHelper::getVariable('APP_URL')
        );
        $webPath = rtrim($appUrl, '/') . '/animate.avif';

        $type = $this->getImageTypeDetector()->detect(
            new MediaFile(
                $webPath,
                'image/avif',
                'avif',
                1024
            ),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(2, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
        static::assertTrue($type->is(ImageType::ANIMATED));

        unlink($publicPath);
    }

    #[Group('needsWebserver')]
    public function testDetectAvifThrowsExceptionOnUnreadableFile(): void
    {
        $path = 'invalid.avif';
        $mediaFile = new MediaFile(
            $path,
            'image/avif',
            'avif',
            1024
        );

        $this->expectException(MediaException::class);
        $this->expectExceptionMessage(MediaException::cannotOpenSourceStreamToRead($path)->getMessage());

        $this->getImageTypeDetector()->detect(
            $mediaFile,
            null
        );
    }

    public function testDetectSvg(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/logo-version-professionalplus.svg'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::VECTOR_GRAPHIC));
    }

    public function testDetectJpg(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada.jpg'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(0, $type->getFlags());
    }

    public function testDetectPng(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo.png'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
    }

    public function testDetectWorksForUpperCase(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/cicada-logo-1.PNG'),
            null
        );

        static::assertInstanceOf(ImageType::class, $type);
        static::assertCount(1, $type->getFlags());
        static::assertTrue($type->is(ImageType::TRANSPARENT));
    }

    public function testDetectDoc(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/reader.doc'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectDocx(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/reader.docx'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectPdf(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.pdf'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectAvi(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.avi'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectMov(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.mov'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectMp4(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.mp4'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectWebm(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/small.webm'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectIso(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/test.iso'),
            null
        );

        static::assertNull($type);
    }

    public function testDetectMp3(): void
    {
        $type = $this->getImageTypeDetector()->detect(
            $this->createMediaFile(__DIR__ . '/../fixtures/file_example.mp3'),
            null
        );

        static::assertNull($type);
    }

    private function getImageTypeDetector(): ImageTypeDetector
    {
        return static::getContainer()->get(ImageTypeDetector::class);
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
