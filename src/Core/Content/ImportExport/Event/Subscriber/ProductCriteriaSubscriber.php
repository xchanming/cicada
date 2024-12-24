<?php declare(strict_types=1);

namespace Cicada\Core\Content\ImportExport\Event\Subscriber;

use Cicada\Core\Content\ImportExport\Event\EnrichExportCriteriaEvent;
use Cicada\Core\Content\ImportExport\ImportExportProfileEntity;
use Cicada\Core\Content\ImportExport\Struct\Config;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('services-settings')]
class ProductCriteriaSubscriber implements EventSubscriberInterface
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
        if ($profile->getSourceEntity() !== ProductDefinition::ENTITY_NAME) {
            return;
        }

        $criteria = $event->getCriteria();
        $criteria->resetSorting();

        $criteria->addSorting(new FieldSorting('autoIncrement'));

        $config = Config::fromLog($event->getLogEntity());

        if ($config->get('includeVariants') !== true) {
            $criteria->addFilter(new EqualsFilter('parentId', null));
        }
    }
}
