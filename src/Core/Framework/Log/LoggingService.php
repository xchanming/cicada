<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Log;

use Cicada\Core\Framework\Event\FlowLogEvent;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('framework')]
class LoggingService implements EventSubscriberInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly string $environment,
        private readonly Logger $logger
    ) {
    }

    public function logFlowEvent(FlowLogEvent $event): void
    {
        $innerEvent = $event->getEvent();

        $additionalData = [];
        $logLevel = Level::Debug;

        if ($innerEvent instanceof LogAware) {
            $logLevel = $innerEvent->getLogLevel();
            $additionalData = $innerEvent->getLogData();
        }

        $this->logger->addRecord(
            $logLevel,
            $innerEvent->getName(),
            [
                'source' => 'core',
                'environment' => $this->environment,
                'additionalData' => $additionalData,
            ]
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [FlowLogEvent::NAME => 'logFlowEvent'];
    }
}
