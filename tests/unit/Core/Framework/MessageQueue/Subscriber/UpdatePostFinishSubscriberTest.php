<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\MessageQueue\Subscriber;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\Registry\TaskRegistry;
use Cicada\Core\Framework\MessageQueue\Subscriber\UpdatePostFinishSubscriber;
use Cicada\Core\Framework\Update\Event\UpdatePostFinishEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(UpdatePostFinishSubscriber::class)]
class UpdatePostFinishSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $events = UpdatePostFinishSubscriber::getSubscribedEvents();

        static::assertCount(1, $events);
        static::assertArrayHasKey(UpdatePostFinishEvent::class, $events);
        static::assertEquals('updatePostFinishEvent', $events[UpdatePostFinishEvent::class]);
    }

    public function testUpdatePostFinishEvent(): void
    {
        $registry = $this->createMock(TaskRegistry::class);
        $registry->expects(static::once())->method('registerTasks');

        (new UpdatePostFinishSubscriber($registry))->updatePostFinishEvent();
    }
}
