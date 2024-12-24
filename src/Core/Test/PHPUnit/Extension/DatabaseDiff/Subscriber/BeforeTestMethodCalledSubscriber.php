<?php declare(strict_types=1);

namespace Cicada\Core\Test\PHPUnit\Extension\DatabaseDiff\Subscriber;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\PHPUnit\Extension\DatabaseDiff\DbState;
use PHPUnit\Event\Test\BeforeTestMethodCalled;
use PHPUnit\Event\Test\BeforeTestMethodCalledSubscriber as BeforeTestMethodCalledSubscriberInterface;

/**
 * @internal
 */
#[Package('core')]
class BeforeTestMethodCalledSubscriber implements BeforeTestMethodCalledSubscriberInterface
{
    public function __construct(private readonly DbState $dbState)
    {
    }

    public function notify(BeforeTestMethodCalled $event): void
    {
        $this->dbState->rememberCurrentDbState();
    }
}
