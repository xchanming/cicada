<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Indexing\Subscriber;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexerRegistry;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class EntityIndexingSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityIndexerRegistry $indexerRegistry)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [EntityWrittenContainerEvent::class => [['refreshIndex', 1000]]];
    }

    public function refreshIndex(EntityWrittenContainerEvent $event): void
    {
        $this->indexerRegistry->refresh($event);
    }
}
