<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\MessageQueue\Subscriber;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Registry\TaskRegistry;
use Cicada\Core\Framework\MessageQueue\Subscriber\PluginLifecycleSubscriber;
use Cicada\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostDeactivateEvent;
use Cicada\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(PluginLifecycleSubscriber::class)]
class PluginLifecycleSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = PluginLifecycleSubscriber::getSubscribedEvents();

        static::assertCount(3, $events);
        static::assertArrayHasKey(PluginPostActivateEvent::class, $events);
        static::assertEquals('afterPluginStateChange', $events[PluginPostActivateEvent::class]);
        static::assertArrayHasKey(PluginPostDeactivateEvent::class, $events);
        static::assertEquals('afterPluginStateChange', $events[PluginPostDeactivateEvent::class]);
        static::assertArrayHasKey(PluginPostUpdateEvent::class, $events);
        static::assertEquals('afterPluginStateChange', $events[PluginPostUpdateEvent::class]);
    }

    public function testRegisterScheduledTasks(): void
    {
        $taskRegistry = $this->createMock(TaskRegistry::class);
        $taskRegistry->expects(static::once())->method('registerTasks');

        $signalCachePool = new ArrayAdapter();
        $subscriber = new PluginLifecycleSubscriber($taskRegistry, $signalCachePool);
        $subscriber->afterPluginStateChange();

        static::assertTrue($signalCachePool->hasItem(StopWorkerOnRestartSignalListener::RESTART_REQUESTED_TIMESTAMP_KEY));
    }
}
