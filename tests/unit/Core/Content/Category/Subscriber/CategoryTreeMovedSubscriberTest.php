<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Category\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\Subscriber\CategoryTreeMovedSubscriber;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\Event\NestedEventCollection;
use Cicada\Core\System\SalesChannel\SalesChannelDefinition;

/**
 * @internal
 */
#[CoversClass(CategoryTreeMovedSubscriber::class)]
class CategoryTreeMovedSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = CategoryTreeMovedSubscriber::getSubscribedEvents();

        static::assertCount(1, $events);
        static::assertArrayHasKey('Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent', $events);
        static::assertSame('detectSalesChannelEntryPoints', $events['Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent']);
    }

    public function testNotRootChange(): void
    {
        $registry = $this->createMock(EntityIndexerRegistry::class);
        $registry->expects(static::never())->method('sendIndexingMessage');
        $subscriber = new CategoryTreeMovedSubscriber($registry);

        $event = new EntityWrittenContainerEvent(Context::createCLIContext(), new NestedEventCollection(), []);
        $subscriber->detectSalesChannelEntryPoints($event);
    }

    public function testDetectSalesChannelEntryPoints(): void
    {
        $registry = $this->createMock(EntityIndexerRegistry::class);
        $registry->expects(static::once())->method('sendIndexingMessage')->with(['category.indexer', 'product.indexer']);
        $subscriber = new CategoryTreeMovedSubscriber($registry);

        $event = new EntityWrittenEvent(
            SalesChannelDefinition::ENTITY_NAME,
            [
                new EntityWriteResult('test', ['navigationCategoryId' => 'asd'], SalesChannelDefinition::ENTITY_NAME, EntityWriteResult::OPERATION_UPDATE),
            ],
            Context::createCLIContext()
        );

        $event = new EntityWrittenContainerEvent(
            Context::createDefaultContext(),
            new NestedEventCollection([$event]),
            []
        );

        $subscriber->detectSalesChannelEntryPoints($event);
    }
}
