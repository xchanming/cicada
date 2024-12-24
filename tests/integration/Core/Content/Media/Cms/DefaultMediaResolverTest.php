<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Media\Cms;

use Cicada\Core\Content\Media\Cms\DefaultMediaResolver;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DefaultMediaResolverTest extends TestCase
{
    use IntegrationTestBehaviour;

    private DefaultMediaResolver $mediaResolver;

    private FilesystemOperator $publicFilesystem;

    protected function setUp(): void
    {
        $this->publicFilesystem = $this->getPublicFilesystem();
        $this->mediaResolver = new DefaultMediaResolver($this->publicFilesystem);
    }

    public function testGetDefaultMediaEntityWithoutValidFileName(): void
    {
        $media = $this->mediaResolver->getDefaultCmsMediaEntity('this/file/does/not/exists');

        static::assertNull($media);
    }

    public function testGetDefaultMediaEntityWithValidFileName(): void
    {
        $this->publicFilesystem->write('/bundles/core/assets/default/cms/cicada.jpg', '');
        $media = $this->mediaResolver->getDefaultCmsMediaEntity('bundles/core/assets/default/cms/cicada.jpg');

        static::assertInstanceOf(MediaEntity::class, $media);
        static::assertEquals('cicada', $media->getFileName());
        static::assertEquals('image/jpeg', $media->getMimeType());
        static::assertEquals('jpg', $media->getFileExtension());
    }
}
