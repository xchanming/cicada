<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Page\Cms;

use Cicada\Core\Content\Media\Cms\AbstractDefaultMediaResolver;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\Adapter\Translation\Translator;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Storefront\Page\Cms\DefaultMediaResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
class DefaultMediaResolverTest extends TestCase
{
    use IntegrationTestBehaviour;

    private DefaultMediaResolver $mediaResolver;

    private MockObject&AbstractDefaultMediaResolver $decorated;

    protected function setUp(): void
    {
        $packages = static::getContainer()->get('assets.packages');

        $translator = $this->createConfiguredMock(
            Translator::class,
            [
                'trans' => 'foobar',
            ]
        );

        $this->decorated = $this->createMock(AbstractDefaultMediaResolver::class);
        $this->mediaResolver = new DefaultMediaResolver($this->decorated, $translator, $packages);
    }

    public function testGetDefaultMediaEntityWithoutValidFileName(): void
    {
        $this->decorated->method('getDefaultCmsMediaEntity')->willReturn(null);
        $media = $this->mediaResolver->getDefaultCmsMediaEntity('this/file/does/not/exists');

        static::assertNull($media);
    }

    public function testGetDefaultMediaEntityWithValidFileName(): void
    {
        $this->decorated->method('getDefaultCmsMediaEntity')->willReturn(new MediaEntity());
        $media = $this->mediaResolver->getDefaultCmsMediaEntity('bundles/storefront/assets/default/cms/cicada.jpg');

        static::assertInstanceOf(MediaEntity::class, $media);

        // ensure url and translations are set correctly
        static::assertStringContainsString('bundles/storefront/assets/default/cms/cicada.jpg', $media->getUrl());
        static::assertSame('foobar', $media->getTranslated()['title']);
        static::assertSame('foobar', $media->getTranslated()['alt']);
    }
}
