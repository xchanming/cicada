<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Media\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Media\Aggregate\MediaFolder\MediaFolderDefinition;
use Cicada\Core\Content\Media\MediaDefinition;
use Cicada\Core\Content\Media\Subscriber\MediaVisibilityRestrictionSubscriber;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Api\Context\AdminApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * @internal
 */
#[CoversClass(MediaVisibilityRestrictionSubscriber::class)]
class MediaVisibilityRestrictionSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $expected = [
            EntitySearchedEvent::class => 'securePrivateFolders',
        ];

        static::assertSame($expected, MediaVisibilityRestrictionSubscriber::getSubscribedEvents());
    }

    public function testSecurePrivateFoldersSystemContextDoesNotGetModified(): void
    {
        $event = new EntitySearchedEvent(
            new Criteria(),
            new MediaFolderDefinition(),
            Context::createCLIContext()
        );

        $subscriber = new MediaVisibilityRestrictionSubscriber();
        $subscriber->securePrivateFolders($event);

        static::assertCount(0, $event->getCriteria()->getFilters());
    }

    public function testSecurePrivateFoldersMediaFolder(): void
    {
        $event = new EntitySearchedEvent(
            new Criteria(),
            new MediaFolderDefinition(),
            Context::createDefaultContext(new AdminApiSource(null))
        );

        $subscriber = new MediaVisibilityRestrictionSubscriber();
        $subscriber->securePrivateFolders($event);

        static::assertCount(1, $event->getCriteria()->getFilters());
    }

    public function testSecurePrivateFoldersMedia(): void
    {
        $event = new EntitySearchedEvent(
            new Criteria(),
            new MediaDefinition(),
            Context::createDefaultContext(new AdminApiSource(null))
        );

        $subscriber = new MediaVisibilityRestrictionSubscriber();
        $subscriber->securePrivateFolders($event);

        static::assertCount(1, $event->getCriteria()->getFilters());
    }

    public function testSecurePrivateFoldersDifferentDefinitionDoesNotGetModified(): void
    {
        $event = new EntitySearchedEvent(
            new Criteria(),
            new ProductDefinition(),
            Context::createDefaultContext(new AdminApiSource(null))
        );

        $subscriber = new MediaVisibilityRestrictionSubscriber();
        $subscriber->securePrivateFolders($event);

        static::assertCount(0, $event->getCriteria()->getFilters());
    }
}
