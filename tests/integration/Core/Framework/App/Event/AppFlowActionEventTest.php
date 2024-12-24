<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\App\Event;

use Cicada\Core\Framework\App\Event\AppFlowActionEvent;
use Cicada\Core\Framework\Webhook\AclPrivilegeCollection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AppFlowActionEventTest extends TestCase
{
    public function testGetter(): void
    {
        $eventName = 'AppFlowActionEvent';
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $payload = [
            'name' => 'value',
        ];

        $event = new AppFlowActionEvent($eventName, $headers, $payload);

        static::assertSame($eventName, $event->getName());
        static::assertEquals($headers, $event->getWebhookHeaders());
        static::assertEquals($payload, $event->getWebhookPayload());
        static::assertTrue($event->isAllowed('11111', new AclPrivilegeCollection([])));
    }
}
