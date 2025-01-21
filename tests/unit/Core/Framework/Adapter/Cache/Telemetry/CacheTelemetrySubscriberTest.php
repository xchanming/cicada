<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Adapter\Cache\Telemetry;

use Cicada\Core\Framework\Adapter\Cache\Telemetry\CacheTelemetrySubscriber;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('framework')]
#[CoversClass(CacheTelemetrySubscriber::class)]
class CacheTelemetrySubscriberTest extends TestCase
{
    public function testEmitInvalidateCacheCountMetric(): void
    {
        $meter = $this->createMock(Meter::class);
        $meter->expects(static::once())
            ->method('emit')
            ->with(static::callback(function (ConfiguredMetric $metric) {
                return $metric->name === 'cache.invalidate.count' && $metric->value === 1;
            }));

        $subscriber = new CacheTelemetrySubscriber($meter);
        $subscriber->emitInvalidateCacheCountMetric();
    }
}
