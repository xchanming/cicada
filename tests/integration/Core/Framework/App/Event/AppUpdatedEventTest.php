<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Event;

use Cicada\Core\Framework\App\AppEntity;
use Cicada\Core\Framework\App\Event\AppInstalledEvent;
use Cicada\Core\Framework\App\Event\AppUpdatedEvent;
use Cicada\Core\Framework\App\Manifest\Manifest;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AppUpdatedEventTest extends TestCase
{
    public function testGetter(): void
    {
        $app = new AppEntity();
        $context = Context::createDefaultContext();
        $event = new AppUpdatedEvent(
            $app,
            Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/test/manifest.xml'),
            $context
        );

        static::assertEquals($app, $event->getApp());
        static::assertEquals($context, $event->getContext());
        static::assertSame(AppUpdatedEvent::NAME, $event->getName());
        static::assertSame([
            'appVersion' => '1.0.0',
        ], $event->getWebhookPayload());
    }

    public function testIsAllowed(): void
    {
        $appId = Uuid::randomHex();
        $app = (new AppEntity())
            ->assign(['id' => $appId]);
        $context = Context::createDefaultContext();
        $event = new AppInstalledEvent(
            $app,
            Manifest::createFromXmlFile(__DIR__ . '/../Manifest/_fixtures/test/manifest.xml'),
            $context
        );

        static::assertTrue($event->isAllowed($appId, new AclPrivilegeCollection([])));
        static::assertFalse($event->isAllowed(Uuid::randomHex(), new AclPrivilegeCollection([])));
    }
}
