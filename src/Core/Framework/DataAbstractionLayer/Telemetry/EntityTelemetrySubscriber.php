<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Telemetry;

use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class EntityTelemetrySubscriber implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly Meter $meter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntitySearchedEvent::class => ['emitAssociationsCountMetric', 99],
        ];
    }

    public function emitAssociationsCountMetric(EntitySearchedEvent $event): void
    {
        $criteria = $event->getCriteria();
        $associationsCount = $this->getAssociationsCountFromCriteria($criteria);
        $this->meter->emit(new ConfiguredMetric(
            name: 'dal.associations.count',
            value: $associationsCount,
        ));
    }

    private function getAssociationsCountFromCriteria(Criteria $criteria): int
    {
        return array_reduce(
            $criteria->getAssociations(),
            fn (int $carry, Criteria $association) => $carry + 1 + $this->getAssociationsCountFromCriteria($association),
            0
        );
    }
}
