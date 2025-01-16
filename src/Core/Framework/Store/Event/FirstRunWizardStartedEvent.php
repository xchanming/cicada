<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Store\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Store\Struct\FrwState;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
class FirstRunWizardStartedEvent extends Event
{
    public function __construct(
        private readonly FrwState $state,
        private readonly Context $context
    ) {
    }

    public function getState(): FrwState
    {
        return $this->state;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
