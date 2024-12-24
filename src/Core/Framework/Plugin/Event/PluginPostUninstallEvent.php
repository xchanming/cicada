<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Context\UninstallContext;
use Cicada\Core\Framework\Plugin\PluginEntity;

#[Package('core')]
class PluginPostUninstallEvent extends PluginLifecycleEvent
{
    public function __construct(
        PluginEntity $plugin,
        private readonly UninstallContext $context
    ) {
        parent::__construct($plugin);
    }

    public function getContext(): UninstallContext
    {
        return $this->context;
    }
}
