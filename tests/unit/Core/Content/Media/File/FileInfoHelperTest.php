<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\File;

use Cicada\Core\Content\Media\File\FileInfoHelper;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Framework\Log\Package;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('discovery')]
#[CoversClass(FileInfoHelper::class)]
class FileInfoHelperTest extends TestCase
{
    private const MIME_TYPE_FOR_UNDETECTED_FORMATS = 'application/octet-stream';

    public function testGetMimeTypeWithDetectableTypeByFileContentWillDetectByContent(): void
    {
        static::assertSame('image/png', FileInfoHelper::getMimeType(__DIR__ . '/_fixtures/image1x1.png', 'glb'));
    }

    public function testGetMimeTypeWithNotDetectableTypeByFileContentWillDetectByExtension(): void
    {
        static::assertSame('model/gltf-binary', FileInfoHelper::getMimeType(__DIR__ . '/_fixtures/binary', 'glb'));
    }

    public function testGetMimeTypeWithNotDetectableTypeByFileContentAndByExtensionWillReturnCommonType(): void
    {
        static::assertSame('application/octet-stream', FileInfoHelper::getMimeType(__DIR__ . '/_fixtures/binary'));
    }

    #[DataProvider('validMimeTypesProvider')]
    public function testGetExtensionWithValidMimeType(string $mimeType, string $expectedExtension): void
    {
        static::assertSame($expectedExtension, FileInfoHelper::getExtension($mimeType));
    }

    public static function validMimeTypesProvider(): \Generator
    {
        yield 'valid mime-type' => ['image/png', 'png'];
        yield 'FileInfoHelper::MIME_TYPE_FOR_UNDETECTED_FORMATS' => [self::MIME_TYPE_FOR_UNDETECTED_FORMATS, 'bin'];
    }

    public function testGetExtensionWithInvalidMimeTypeThrowsException(): void
    {
        $this->expectExceptionObject(MediaException::invalidMimeType('invalid/mime-type'));
        static::assertSame('bin', FileInfoHelper::getExtension('invalid/mime-type'));
    }
}
