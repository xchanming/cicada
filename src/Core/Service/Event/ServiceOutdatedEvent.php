<?php declare(strict_types=1);

namespace Cicada\Core\Service\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
readonly class ServiceOutdatedEvent implements CicadaEvent
{
    public function __construct(public string $serviceName, private Context $context)
    {
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
