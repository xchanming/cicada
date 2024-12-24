<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Twig\Extension;

use Cicada\Core\Content\Media\Core\Params\UrlParams;
use Cicada\Core\Content\Media\Infrastructure\Path\MediaUrlGenerator;
use Cicada\Core\Content\Media\MediaEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Framework\Twig\Extension\UrlEncodingTwigFilter;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('storefront')]
#[CoversClass(UrlEncodingTwigFilter::class)]
class UrlEncodingTwigFilterTest extends TestCase
{
    public function testHappyPath(): void
    {
        $filter = new UrlEncodingTwigFilter();
        $url = 'https://cicada.com:80/some/thing';
        static::assertEquals($url, $filter->encodeUrl($url));
    }

    public function testReturnsNullsIfNoUrlIsGiven(): void
    {
        $filter = new UrlEncodingTwigFilter();
        static::assertNull($filter->encodeUrl(null));
    }

    public function testItEncodesWithoutPort(): void
    {
        $filter = new UrlEncodingTwigFilter();
        $url = 'https://cicada.com/some/thing';
        static::assertEquals($url, $filter->encodeUrl($url));
    }

    public function testRespectsQueryParameter(): void
    {
        $filter = new UrlEncodingTwigFilter();
        $url = 'https://cicada.com/some/thing?a=3&b=25';
        static::assertEquals($url, $filter->encodeUrl($url));
    }

    public function testReturnsEncodedPathsWithoutHostAndScheme(): void
    {
        $filter = new UrlEncodingTwigFilter();
        static::assertEquals(
            'cicada.com/some/thing',
            $filter->encodeUrl('cicada.com/some/thing')
        );
    }

    public function testItEncodesSpaces(): void
    {
        $filter = new UrlEncodingTwigFilter();
        static::assertEquals(
            'https://cicada.com:80/so%20me/thing%20new.jpg',
            $filter->encodeUrl('https://cicada.com:80/so me/thing new.jpg')
        );
    }

    public function testItEncodesSpecialCharacters(): void
    {
        $filter = new UrlEncodingTwigFilter();
        static::assertEquals(
            'https://cicada.com:80/so%20me/thing%20new.jpg',
            $filter->encodeUrl('https://cicada.com:80/so me/thing new.jpg')
        );
    }

    public function testItReturnsNullIfMediaIsNull(): void
    {
        $filter = new UrlEncodingTwigFilter();
        static::assertNull($filter->encodeMediaUrl(null));
    }

    public function testNullIfNoMediaIsUploaded(): void
    {
        $filter = new UrlEncodingTwigFilter();
        $media = new MediaEntity();

        static::assertNull($filter->encodeMediaUrl($media));
    }

    public function testItEncodesTheUrl(): void
    {
        $filter = new UrlEncodingTwigFilter();

        $filesystem = new Filesystem(new InMemoryFilesystemAdapter(), ['public_url' => 'http://localhost:8000']);

        $urlGenerator = new MediaUrlGenerator($filesystem);
        $uploadTime = new \DateTime();

        $media = new MediaEntity();
        $media->setId(Uuid::randomHex());
        $media->setMimeType('image/png');
        $media->setFileExtension('png');
        $media->setUploadedAt($uploadTime);
        $media->setFileName('(image with spaces and brackets)');
        $media->setPath('(image with spaces and brackets).png');

        $urls = $urlGenerator->generate(['foo' => UrlParams::fromMedia($media)]);

        static::assertArrayHasKey('foo', $urls);
        $url = $urls['foo'];

        $media->setUrl((string) $url);

        static::assertStringEndsWith('%28image%20with%20spaces%20and%20brackets%29.png', (string) $filter->encodeMediaUrl($media));
    }
}
