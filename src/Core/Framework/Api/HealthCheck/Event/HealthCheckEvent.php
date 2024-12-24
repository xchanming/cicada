<?php

declare(strict_types=1);

namespace Cicada\Core\Framework\Api\HealthCheck\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('core')]
class HealthCheckEvent extends Event
{
    public function __construct(
        public readonly Context $context
    ) {
    }
}
