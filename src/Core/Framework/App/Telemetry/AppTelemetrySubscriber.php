<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Telemetry;

use Cicada\Core\Framework\App\Event\AppInstalledEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class AppTelemetrySubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly Meter $meter)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AppInstalledEvent::class => 'emitAppInstalledMetric',
        ];
    }

    public function emitAppInstalledMetric(): void
    {
        $this->meter->emit(new ConfiguredMetric(name: 'app.install.count', value: 1));
    }
}
