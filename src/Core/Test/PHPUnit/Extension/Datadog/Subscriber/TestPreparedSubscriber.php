<?php declare(strict_types=1);

namespace Cicada\Core\Test\PHPUnit\Extension\Datadog\Subscriber;

use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\PHPUnit\Extension\Common\TimeKeeper;

/**
 * @internal
 */
#[Package('core')]
class TestPreparedSubscriber implements PreparedSubscriber
{
    public function __construct(private readonly TimeKeeper $timeKeeper)
    {
    }

    public function notify(Prepared $event): void
    {
        $this->timeKeeper->start(
            $event->test()->id(),
            $event->telemetryInfo()->time()
        );
    }
}
