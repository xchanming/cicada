<?php declare(strict_types=1);

namespace Cicada\Core\Test\Stub\Flow;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\EventData\EventDataCollection;
use Cicada\Core\Framework\Event\FlowEventAware;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class DummyEvent extends Event implements FlowEventAware
{
    public static function getAvailableData(): EventDataCollection
    {
        return new EventDataCollection();
    }

    public function getName(): string
    {
        return 'dummy.event';
    }

    public function getContext(): Context
    {
        return Context::createDefaultContext();
    }
}
