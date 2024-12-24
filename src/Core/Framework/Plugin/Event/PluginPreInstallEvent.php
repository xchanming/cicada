<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Plugin\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Context\InstallContext;
use Cicada\Core\Framework\Plugin\PluginEntity;

#[Package('core')]
class PluginPreInstallEvent extends PluginLifecycleEvent
{
    public function __construct(
        PluginEntity $plugin,
        private readonly InstallContext $context
    ) {
        parent::__construct($plugin);
    }

    public function getContext(): InstallContext
    {
        return $this->context;
    }
}
