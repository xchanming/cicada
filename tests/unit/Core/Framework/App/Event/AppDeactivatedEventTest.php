<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\App\Event;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Event\AppDeactivatedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(AppDeactivatedEvent::class)]
class AppDeactivatedEventTest extends TestCase
{
    public function testGetter(): void
    {
        $app = new AppEntity();
        $context = Context::createDefaultContext();
        $event = new AppDeactivatedEvent(
            $app,
            $context
        );

        static::assertEquals($app, $event->getApp());
        static::assertEquals($context, $event->getContext());
        static::assertSame(AppDeactivatedEvent::NAME, $event->getName());
        static::assertSame([], $event->getWebhookPayload());
    }

    public function testIsAllowed(): void
    {
        $appId = Uuid::randomHex();
        $app = (new AppEntity())
            ->assign(['id' => $appId]);
        $context = Context::createDefaultContext();
        $event = new AppDeactivatedEvent(
            $app,
            $context
        );

        static::assertTrue($event->isAllowed($appId, new AclPrivilegeCollection([])));
        static::assertFalse($event->isAllowed(Uuid::randomHex(), new AclPrivilegeCollection([])));
    }
}
