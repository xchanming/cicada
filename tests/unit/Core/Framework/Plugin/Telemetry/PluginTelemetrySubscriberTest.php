<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Plugin\Telemetry;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Telemetry\PluginTelemetrySubscriber;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
#[CoversClass(PluginTelemetrySubscriber::class)]
class PluginTelemetrySubscriberTest extends TestCase
{
    public function testEmitPluginInstallCountMetric(): void
    {
        $meter = $this->createMock(Meter::class);
        $meter->expects(static::once())
            ->method('emit')
            ->with(static::callback(function (ConfiguredMetric $metric) {
                return $metric->name === 'plugin.install.count' && $metric->value === 1;
            }));

        $subscriber = new PluginTelemetrySubscriber($meter);
        $subscriber->emitPluginInstallCountMetric();
    }
}
