<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Telemetry;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Exception\MetricNotSupportedException;
use Cicada\Core\Framework\Telemetry\Metrics\Exception\MissingMetricConfigurationException;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\Metric;
use Cicada\Core\Framework\Telemetry\Metrics\MetricTransportInterface;

/**
 * @experimental feature:TELEMETRY_METRICS stableVersion:v6.7.0
 */
#[Package('core')]
abstract class TelemetryException extends HttpException
{
    public static function metricNotSupported(
        Metric $metric,
        MetricTransportInterface $transport
    ): MetricNotSupportedException {
        return new MetricNotSupportedException(
            metric: $metric,
            transport: $transport,
            message: \sprintf('Metric %s, not supported by transport %s', $metric::class, $transport::class),
        );
    }

    /**
     * @internal
     */
    public static function metricMissingConfiguration(string $metric): MissingMetricConfigurationException
    {
        return new MissingMetricConfigurationException(
            metric: $metric,
            message: \sprintf('Missing configuration for metric %s', $metric),
        );
    }
}
