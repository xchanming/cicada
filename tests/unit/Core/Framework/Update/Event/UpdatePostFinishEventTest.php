<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Update\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent;

/**
 * @internal
 */
#[CoversClass(UpdatePostFinishEvent::class)]
class UpdatePostFinishEventTest extends TestCase
{
    public function testGetters(): void
    {
        $context = Context::createDefaultContext();
        $event = new UpdatePostFinishEvent($context, 'oldVersion', 'newVersion');

        static::assertSame('oldVersion', $event->getOldVersion());
        static::assertSame('newVersion', $event->getNewVersion());
        static::assertSame($context, $event->getContext());
    }
}
