<?php
declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Framework\Media;

use Cicada\Core\Content\Media\File\FileSaver;
use Cicada\Core\Content\Media\MediaException;
use Cicada\Core\Content\Media\MediaService;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Storefront\Framework\Media\Exception\FileTypeNotAllowedException;
use Cicada\Storefront\Framework\Media\Exception\MediaValidatorMissingException;
use Cicada\Storefront\Framework\Media\StorefrontMediaUploader;
use Cicada\Storefront\Framework\Media\StorefrontMediaValidatorRegistry;
use Cicada\Storefront\Framework\StorefrontFrameworkException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @internal
 */
#[Package('buyers-experience')]
class StorefrontMediaUploaderTest extends TestCase
{
    use KernelTestBehaviour;

    final public const FIXTURE_DIR = __DIR__ . '/fixtures';

    public function testUploadDocument(): void
    {
        $file = $this->getUploadFixture('empty.pdf');
        $result = $this->getUploadService()->upload($file, 'test', 'documents', Context::createDefaultContext());

        $repo = static::getContainer()->get('media.repository');
        static::assertEquals(1, $repo->search(new Criteria([$result]), Context::createDefaultContext())->getTotal());
        $this->removeMedia($result);
    }

    public function testUploadDocumentFailIllegalFileType(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectException(FileTypeNotAllowedException::class);
        } else {
            $this->expectException(StorefrontFrameworkException::class);
        }
        $this->expectExceptionMessage('Type "application/vnd.ms-excel" of provided file is not allowed for documents');

        $file = $this->getUploadFixture('empty.xls');
        $this->getUploadService()->upload($file, 'test', 'documents', Context::createDefaultContext());
    }

    public function testUploadDocumentFailFilenameContainsPhp(): void
    {
        $this->expectException(MediaException::class);
        $this->expectExceptionMessage('Provided filename "contains.php.pdf" is not permitted: contains PHP related file extension');

        $file = $this->getUploadFixture('contains.php.pdf');
        $this->getUploadService()->upload($file, 'test', 'documents', Context::createDefaultContext());
    }

    public function testUploadImage(): void
    {
        $file = $this->getUploadFixture('image.png');
        $result = $this->getUploadService()->upload($file, 'test', 'images', Context::createDefaultContext());

        $repo = static::getContainer()->get('media.repository');
        static::assertEquals(1, $repo->search(new Criteria([$result]), Context::createDefaultContext())->getTotal());
        $this->removeMedia($result);
    }

    public function testUploadDocumentFailIllegalImageType(): void
    {
        if (!Feature::isActive('v6.7.0.0')) {
            $this->expectException(FileTypeNotAllowedException::class);
        } else {
            $this->expectException(StorefrontFrameworkException::class);
        }
        $this->expectExceptionMessage('Type "image/webp" of provided file is not allowed for images');

        $file = $this->getUploadFixture('image.webp');
        $this->getUploadService()->upload($file, 'test', 'images', Context::createDefaultContext());
    }

    public function testUploadUnknownType(): void
    {
        $this->expectException(MediaValidatorMissingException::class);
        $this->expectExceptionMessage('No validator for notExistingType was found.');

        $file = $this->getUploadFixture('image.png');
        $this->getUploadService()->upload($file, 'test', 'notExistingType', Context::createDefaultContext());
    }

    private function getUploadFixture(string $filename): UploadedFile
    {
        return new UploadedFile(self::FIXTURE_DIR . '/' . $filename, $filename, null, null, true);
    }

    private function getUploadService(): StorefrontMediaUploader
    {
        return new StorefrontMediaUploader(
            static::getContainer()->get(MediaService::class),
            static::getContainer()->get(FileSaver::class),
            static::getContainer()->get(StorefrontMediaValidatorRegistry::class)
        );
    }

    private function removeMedia(string $ids): void
    {
        $ids = [$ids];

        static::getContainer()->get('media.repository')->delete(
            array_map(static fn (string $id) => ['id' => $id], $ids),
            Context::createDefaultContext()
        );
    }
}
