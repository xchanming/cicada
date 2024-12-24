<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Context\UpdateContext;
use Cicada\Core\Framework\Plugin\PluginEntity;

#[Package('core')]
class PluginPreUpdateEvent extends PluginLifecycleEvent
{
    public function __construct(
        PluginEntity $plugin,
        private readonly UpdateContext $context
    ) {
        parent::__construct($plugin);
    }

    public function getContext(): UpdateContext
    {
        return $this->context;
    }
}
