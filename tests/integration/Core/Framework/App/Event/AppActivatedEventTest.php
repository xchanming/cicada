<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Event;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Event\AppActivatedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;

/**
 * @internal
 */
class AppActivatedEventTest extends TestCase
{
    public function testGetter(): void
    {
        $app = new AppEntity();
        $context = Context::createDefaultContext();
        $event = new AppActivatedEvent(
            $app,
            $context
        );

        static::assertEquals($app, $event->getApp());
        static::assertEquals($context, $event->getContext());
        static::assertSame(AppActivatedEvent::NAME, $event->getName());
        static::assertSame([], $event->getWebhookPayload());
    }

    public function testIsAllowed(): void
    {
        $appId = Uuid::randomHex();
        $app = (new AppEntity())
            ->assign(['id' => $appId]);
        $context = Context::createDefaultContext();
        $event = new AppActivatedEvent(
            $app,
            $context
        );

        static::assertTrue($event->isAllowed($appId, new AclPrivilegeCollection([])));
        static::assertFalse($event->isAllowed(Uuid::randomHex(), new AclPrivilegeCollection([])));
    }
}
