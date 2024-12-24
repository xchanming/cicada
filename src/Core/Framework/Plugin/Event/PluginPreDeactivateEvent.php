<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Context\DeactivateContext;
use Cicada\Core\Framework\Plugin\PluginEntity;

#[Package('core')]
class PluginPreDeactivateEvent extends PluginLifecycleEvent
{
    public function __construct(
        PluginEntity $plugin,
        private readonly DeactivateContext $context
    ) {
        parent::__construct($plugin);
    }

    public function getContext(): DeactivateContext
    {
        return $this->context;
    }
}
