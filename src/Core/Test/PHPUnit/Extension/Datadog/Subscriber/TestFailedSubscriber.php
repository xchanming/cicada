<?php declare(strict_types=1);

namespace Cicada\Core\Test\PHPUnit\Extension\Datadog\Subscriber;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\PHPUnit\Extension\Common\TimeKeeper;
use Cicada\Core\Test\PHPUnit\Extension\Datadog\DatadogPayload;
use Cicada\Core\Test\PHPUnit\Extension\Datadog\DatadogPayloadCollection;
use PHPUnit\Event\Telemetry\HRTime;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;

/**
 * @internal
 */
#[Package('framework')]
class TestFailedSubscriber implements FailedSubscriber
{
    public function __construct(
        private readonly TimeKeeper $timeKeeper,
        private readonly DatadogPayloadCollection $failedTests
    ) {
    }

    public function notify(Failed $event): void
    {
        $time = $event->telemetryInfo()->time();

        $duration = $this->timeKeeper->stop(
            $event->test()->id(),
            HRTime::fromSecondsAndNanoseconds(
                $time->seconds(),
                $time->nanoseconds(),
            ),
        );

        $payload = new DatadogPayload(
            'phpunit',
            'phpunit,test:failed',
            $event->asString(),
            'PHPUnit',
            $event->test()->id(),
            $duration->asFloat()
        );

        $this->failedTests->set($event->test()->id(), $payload);
    }
}
