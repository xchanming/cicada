<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Infrastructure\Path;

use Cicada\Core\Content\Media\Core\Application\AbstractMediaUrlGenerator;
use Cicada\Core\Content\Media\Core\Params\UrlParams;
use Cicada\Core\Content\Media\Core\Params\UrlParamsSource;
use Cicada\Core\Content\Media\Infrastructure\Path\MediaUrlGenerator;
use Cicada\Core\Content\Media\MediaException;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(MediaUrlGenerator::class)]
#[CoversClass(AbstractMediaUrlGenerator::class)]
class MediaUrlGeneratorTest extends TestCase
{
    #[DataProvider('generateProvider')]
    public function testGenerate(UrlParams $params, ?string $expected): void
    {
        $generator = new MediaUrlGenerator(
            new Filesystem(new InMemoryFilesystemAdapter(), ['public_url' => 'http://localhost:8000']),
        );

        if ($expected === null) {
            $this->expectException(MediaException::class);
        }

        $url = $generator->generate([$params]);

        static::assertSame([$expected], $url);
    }

    public static function generateProvider(): \Generator
    {
        yield 'Test with empty path' => [
            new UrlParams('id', UrlParamsSource::MEDIA, '', null),
            'http://localhost:8000/',
        ];

        yield 'Test with path' => [
            new UrlParams('id', UrlParamsSource::MEDIA, 'test.jpg', null),
            'http://localhost:8000/test.jpg',
        ];

        yield 'Test with longer path' => [
            new UrlParams('id', UrlParamsSource::MEDIA, 'media/foo/3a/test.jpg', null),
            'http://localhost:8000/media/foo/3a/test.jpg',
        ];

        yield 'Test with date' => [
            new UrlParams('id', UrlParamsSource::MEDIA, 'test.jpg', new \DateTimeImmutable('2021-01-01')),
            'http://localhost:8000/test.jpg?ts=1609430400',
        ];

        yield 'Test with path is an external url' => [
            new UrlParams('id', UrlParamsSource::MEDIA, 'https://test.com/photo/flower.jpg', null),
            'https://test.com/photo/flower.jpg',
        ];
    }
}
