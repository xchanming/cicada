<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Update\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Update\Event\UpdatePrePrepareEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdatePrePrepareEvent::class)]
class UpdatePrePrepareEventTest extends TestCase
{
    public function testGetters(): void
    {
        $context = Context::createDefaultContext();
        $event = new UpdatePrePrepareEvent($context, 'currentVersion', 'newVersion');

        static::assertSame('currentVersion', $event->getCurrentVersion());
        static::assertSame('newVersion', $event->getNewVersion());
        static::assertSame($context, $event->getContext());
    }
}
