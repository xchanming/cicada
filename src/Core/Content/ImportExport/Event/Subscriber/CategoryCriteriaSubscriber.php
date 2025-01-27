<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Event\Subscriber;

use Cicada\Core\Content\Category\CategoryDefinition;
use Cicada\Core\Content\ImportExport\Event\EnrichExportCriteriaEvent;
use Cicada\Core\Content\ImportExport\ImportExportProfileEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class CategoryCriteriaSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EnrichExportCriteriaEvent::class => 'enrich',
        ];
    }

    public function enrich(EnrichExportCriteriaEvent $event): void
    {
        /** @var ImportExportProfileEntity $profile */
        $profile = $event->getLogEntity()->getProfile();
        if ($profile->getSourceEntity() !== CategoryDefinition::ENTITY_NAME) {
            return;
        }

        $criteria = $event->getCriteria();
        $criteria->resetSorting();

        $criteria->addSorting(new FieldSorting('level'));
        $criteria->addSorting(new FieldSorting('id'));
    }
}
