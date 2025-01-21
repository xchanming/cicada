<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Telemetry\Metrics\Factory;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Config\TransportConfig;
use Cicada\Core\Framework\Telemetry\Metrics\MetricTransportInterface;

/**
 * @experimental feature:TELEMETRY_METRICS stableVersion:v6.7.0
 */
#[Package('framework')]
interface MetricTransportFactoryInterface
{
    public function create(TransportConfig $transportConfig): MetricTransportInterface;
}
