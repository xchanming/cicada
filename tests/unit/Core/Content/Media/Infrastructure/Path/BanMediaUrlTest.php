<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Infrastructure\Path;

use Cicada\Core\Content\Media\Core\Application\MediaReverseProxy;
use Cicada\Core\Content\Media\Event\MediaPathChangedEvent;
use Cicada\Core\Content\Media\Infrastructure\Path\BanMediaUrl;
use Cicada\Core\Content\Media\Infrastructure\Path\MediaUrlGenerator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(BanMediaUrl::class)]
class BanMediaUrlTest extends TestCase
{
    public function testNotCalledWhenGatewayDisabled(): void
    {
        $gateway = new Gateway(false);

        $generator = $this->createMock(MediaUrlGenerator::class);

        $generator->expects(static::never())
            ->method('generate');

        $banMediaUrl = new BanMediaUrl($gateway, $generator);

        $event = new MediaPathChangedEvent(Context::createDefaultContext());
        $banMediaUrl->changed($event);

        static::assertSame([], $gateway->urls);
    }

    public function testNotCalledWithEmptyEvent(): void
    {
        $gateway = $this->createMock(MediaReverseProxy::class);
        $gateway->method('enabled')->willReturn(true);
        $gateway->expects(static::never())->method('ban');

        $generator = $this->createMock(MediaUrlGenerator::class);
        $generator->expects(static::never())->method('generate');

        $banMediaUrl = new BanMediaUrl($gateway, $generator);

        $event = new MediaPathChangedEvent(Context::createDefaultContext());
        $banMediaUrl->changed($event);
    }

    public function testNotCalledWhenGeneratorReturnsEmptyUrls(): void
    {
        $gateway = $this->createMock(MediaReverseProxy::class);
        $gateway->method('enabled')->willReturn(true);
        $gateway->expects(static::never())->method('ban');

        $generator = $this->createMock(MediaUrlGenerator::class);
        $generator->method('generate')->willReturn([]);

        $banMediaUrl = new BanMediaUrl($gateway, $generator);

        $event = new MediaPathChangedEvent(Context::createDefaultContext());
        $banMediaUrl->changed($event);
    }

    public function testCalledWithMediaUrls(): void
    {
        $gateway = new Gateway(true);

        $generator = new MediaUrlGenerator(
            new Filesystem(new InMemoryFilesystemAdapter(), ['public_url' => 'http://localhost:8000'])
        );

        $banMediaUrl = new BanMediaUrl($gateway, $generator);

        $event = new MediaPathChangedEvent(Context::createDefaultContext());
        $ids = new IdsCollection();

        $event->media(mediaId: $ids->get('media'), path: 'media.png');
        $event->thumbnail(
            mediaId: $ids->get('media'),
            thumbnailId: $ids->get('thumbnail'),
            path: 'thumbnail.png'
        );

        $banMediaUrl->changed($event);

        $expected = [
            'http://localhost:8000/media.png',
            'http://localhost:8000/thumbnail.png',
        ];

        static::assertSame($expected, $gateway->urls);
    }
}

/**
 * @internal
 */
class Gateway implements MediaReverseProxy
{
    /**
     * @var array<string>
     */
    public array $urls = [];

    private bool $enabled;

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param array<string> $urls
     */
    public function ban(array $urls): void
    {
        $this->urls = $urls;
    }
}
