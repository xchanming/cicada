<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Elasticsearch\Admin\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\Event\RefreshIndexEvent;
use Cicada\Elasticsearch\Admin\AdminIndexingBehavior;
use Cicada\Elasticsearch\Admin\AdminSearchRegistry;
use Cicada\Elasticsearch\Admin\Subscriber\RefreshIndexSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RefreshIndexSubscriber::class)]
class RefreshIndexSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertArrayHasKey(RefreshIndexEvent::class, RefreshIndexSubscriber::getSubscribedEvents());
    }

    public function testHandedWithSkipOption(): void
    {
        $registry = $this->createMock(AdminSearchRegistry::class);
        $registry->expects(static::once())->method('iterate')->with(new AdminIndexingBehavior(false, ['product']));

        $subscriber = new RefreshIndexSubscriber($registry);
        $subscriber->handled(new RefreshIndexEvent(false, ['product']));
    }

    public function testHandedWithOnlyOption(): void
    {
        $registry = $this->createMock(AdminSearchRegistry::class);
        $registry->expects(static::once())->method('iterate')->with(new AdminIndexingBehavior(false, [], ['product']));

        $subscriber = new RefreshIndexSubscriber($registry);
        $subscriber->handled(new RefreshIndexEvent(false, [], ['product']));
    }
}
