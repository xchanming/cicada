<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Telemetry\Factory;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Config\TransportConfig;
use Cicada\Core\Framework\Telemetry\Metrics\Factory\MetricTransportFactoryInterface;
use Cicada\Core\Framework\Telemetry\Metrics\MetricTransportInterface;
use Cicada\Core\Framework\Test\Telemetry\Transport\TraceableTransport;

/**
 * @internal
 */
#[Package('core')]
class TraceableTransportFactory implements MetricTransportFactoryInterface
{
    public function create(TransportConfig $transportConfig): MetricTransportInterface
    {
        return new TraceableTransport();
    }
}
