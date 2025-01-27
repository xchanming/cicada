<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Indexing\Subscriber;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\Subscriber\EntityIndexingSubscriber;
use Cicada\Core\Framework\Event\NestedEventCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityIndexingSubscriber::class)]
class EntityIndexingSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        static::assertArrayHasKey(EntityWrittenContainerEvent::class, EntityIndexingSubscriber::getSubscribedEvents());
    }

    public function testRefresh(): void
    {
        $registry = $this->createMock(EntityIndexerRegistry::class);
        $registry->expects(static::once())->method('refresh');

        $subscriber = new EntityIndexingSubscriber($registry);
        $subscriber->refreshIndex(new EntityWrittenContainerEvent(Context::createDefaultContext(), new NestedEventCollection(), []));
    }
}
