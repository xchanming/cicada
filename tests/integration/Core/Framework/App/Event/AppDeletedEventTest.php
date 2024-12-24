<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Event;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\App\Event\AppDeletedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;

/**
 * @internal
 */
class AppDeletedEventTest extends TestCase
{
    public function testGetter(): void
    {
        $appId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $event = new AppDeletedEvent(
            $appId,
            $context
        );

        static::assertEquals($appId, $event->getAppId());
        static::assertEquals($context, $event->getContext());
        static::assertSame(AppDeletedEvent::NAME, $event->getName());
        static::assertSame(['keepUserData' => false], $event->getWebhookPayload());
    }

    public function testIsAllowed(): void
    {
        $appId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $event = new AppDeletedEvent(
            $appId,
            $context
        );

        static::assertTrue($event->isAllowed($appId, new AclPrivilegeCollection([])));
        static::assertFalse($event->isAllowed(Uuid::randomHex(), new AclPrivilegeCollection([])));
    }
}
