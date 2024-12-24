<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Telemetry\Metrics\Config;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\Type;

/**
 * @internal
 *
 * @phpstan-import-type MetricDefinition from MetricConfig
 */
#[Package('core')]
class TransportConfigProvider
{
    public function __construct(private readonly MetricConfigProvider $metricConfigProvider)
    {
    }

    public function getTransportConfig(): TransportConfig
    {
        return new TransportConfig(metricsConfig: $this->metricConfigProvider->all());
    }
}
