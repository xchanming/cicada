<?php declare(strict_types=1);

namespace Cicada\Core\Framework\MessageQueue\Telemetry;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\Service\MessageSizeCalculator;
use Cicada\Core\Framework\Telemetry\Metrics\Meter;
use Cicada\Core\Framework\Telemetry\Metrics\Metric\ConfiguredMetric;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

/**
 * @internal
 */
#[Package('services-settings')]
class MessageQueueTelemetrySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Meter $meter,
        private readonly MessageSizeCalculator $messageSizeCalculator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'emitMessageSizeMetric',
        ];
    }

    public function emitMessageSizeMetric(WorkerMessageReceivedEvent $event): void
    {
        $this->meter->emit(new ConfiguredMetric(
            name: 'messenger.message.size',
            value: $this->messageSizeCalculator->size($event->getEnvelope()),
        ));
    }
}
