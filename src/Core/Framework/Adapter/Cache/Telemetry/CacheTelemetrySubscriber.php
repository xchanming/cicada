<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Adapter\Cache\Telemetry;

use Cicada\Core\Framework\Adapter\Cache\InvalidateCacheEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class CacheTelemetrySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Meter $meter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvalidateCacheEvent::class => 'emitInvalidateCacheCountMetric',
        ];
    }

    public function emitInvalidateCacheCountMetric(): void
    {
        $this->meter->emit(new ConfiguredMetric('cache.invalidate.count', 1));
    }
}
