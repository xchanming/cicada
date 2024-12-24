<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Core\Event;

use Cicada\Core\Content\Media\Core\Event\UpdateMediaPathEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdateMediaPathEvent::class)]
class UpdateMediaPathEventTest extends TestCase
{
    public function testGetIterator(): void
    {
        $event = new UpdateMediaPathEvent(['foo', 'bar']);

        static::assertSame(['foo', 'bar'], iterator_to_array($event->getIterator()));
    }
}
