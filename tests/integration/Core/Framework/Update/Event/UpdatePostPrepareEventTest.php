<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Update\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Update\Event\UpdatePostPrepareEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(UpdatePostPrepareEvent::class)]
class UpdatePostPrepareEventTest extends TestCase
{
    public function testGetters(): void
    {
        $context = Context::createDefaultContext();
        $event = new UpdatePostPrepareEvent($context, 'currentVersion', 'newVersion');

        static::assertSame('currentVersion', $event->getCurrentVersion());
        static::assertSame('newVersion', $event->getNewVersion());
        static::assertSame($context, $event->getContext());
    }
}
