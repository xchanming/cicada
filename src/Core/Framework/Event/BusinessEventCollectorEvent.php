<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
class BusinessEventCollectorEvent extends NestedEvent
{
    final public const NAME = 'collect.business-events';

    public function __construct(
        private readonly BusinessEventCollectorResponse $events,
        private readonly Context $context
    ) {
    }

    public function getCollection(): BusinessEventCollectorResponse
    {
        return $this->events;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
