<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Core\Event;

use Cicada\Core\Content\Media\Core\Event\ThumbnailLocationEvent;
use Cicada\Core\Content\Media\Core\Params\MediaLocationStruct;
use Cicada\Core\Content\Media\Core\Params\ThumbnailLocationStruct;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ThumbnailLocationEvent::class)]
class ThumbnailLocationEventTest extends TestCase
{
    public function testGetIterator(): void
    {
        $media = new MediaLocationStruct('foo', 'foo', 'foo', null);

        $locations = [
            'foo' => new ThumbnailLocationStruct('foo', 100, 101, $media),
            'bar' => new ThumbnailLocationStruct('bar', 100, 101, $media),
        ];

        $event = new ThumbnailLocationEvent($locations);

        static::assertSame($locations, iterator_to_array($event->getIterator()));
    }
}
