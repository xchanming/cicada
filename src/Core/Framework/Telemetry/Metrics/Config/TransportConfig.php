<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Telemetry\Metrics\Config;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('core')]
readonly class TransportConfig
{
    /**
     * @param array<MetricConfig> $metricsConfig
     */
    public function __construct(public array $metricsConfig)
    {
    }
}
