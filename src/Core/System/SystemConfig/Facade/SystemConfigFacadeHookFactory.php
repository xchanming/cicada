<?php declare(strict_types=1);

namespace Cicada\Core\System\SystemConfig\Facade;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\HookServiceFactory;
use Cicada\Core\Framework\Script\Execution\Awareness\SalesChannelContextAware;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\Framework\Script\Execution\Script;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
#[Package('services-settings')]
class SystemConfigFacadeHookFactory extends HookServiceFactory
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly Connection $connection
    ) {
    }

    public function getName(): string
    {
        return 'config';
    }

    public function factory(Hook $hook, Script $script): SystemConfigFacade
    {
        $salesChannelId = null;

        if ($hook instanceof SalesChannelContextAware) {
            $salesChannelId = $hook->getSalesChannelContext()->getSalesChannelId();
        }

        return new SystemConfigFacade($this->systemConfigService, $this->connection, $script->getScriptAppInformation(), $salesChannelId);
    }
}
