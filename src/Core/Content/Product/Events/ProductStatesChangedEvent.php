<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Content\Product\DataAbstractionLayer\UpdatedStates;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaEvent;
use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('inventory')]
class ProductStatesChangedEvent extends Event implements CicadaEvent
{
    /**
     * @param UpdatedStates[] $updatedStates
     */
    public function __construct(
        protected array $updatedStates,
        protected Context $context
    ) {
    }

    /**
     * @return UpdatedStates[]
     */
    public function getUpdatedStates(): array
    {
        return $this->updatedStates;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
