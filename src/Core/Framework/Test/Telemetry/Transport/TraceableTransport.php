<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Telemetry\Transport;

use Cicada\Core\Framework\Telemetry\Metrics\Metric\Metric;
use Cicada\Core\Framework\Telemetry\Metrics\MetricTransportInterface;

/**
 * @internal
 */
class TraceableTransport implements MetricTransportInterface
{
    /**
     * @var Metric[]
     */
    private array $metrics = [];

    public function emit(Metric $metric): void
    {
        $this->metrics[] = $metric;
    }

    /**
     * @return Metric[]
     */
    public function getEmittedMetrics(): array
    {
        return $this->metrics;
    }

    public function reset(): self
    {
        $this->metrics = [];

        return $this;
    }
}
