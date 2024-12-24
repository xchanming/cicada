<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\DataAbstractionLayer\Telemetry;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntitySearchedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Telemetry\EntityTelemetrySubscriber;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(EntityTelemetrySubscriber::class)]
class EntityTelemetrySubscriberTest extends TestCase
{
    public function testEmitAssociationsCountMetric(): void
    {
        $criteria = new Criteria();
        $criteria->addAssociation('association1');
        $criteria->addAssociation('association2');

        $event = new EntitySearchedEvent($criteria, $this->createMock(EntityDefinition::class), Context::createDefaultContext());
        $meter = $this->createMock(Meter::class);
        $meter->expects(static::once())
            ->method('emit')
            ->with(static::callback(function (ConfiguredMetric $metric) {
                return $metric->name === 'dal.associations.count' && $metric->value === 2;
            }));

        $subscriber = new EntityTelemetrySubscriber($meter);
        $subscriber->emitAssociationsCountMetric($event);
    }
}
