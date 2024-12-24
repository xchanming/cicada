<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Facade;

use Cicada\Core\Framework\Api\Sync\SyncService;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Awareness\HookServiceFactory;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\Framework\Script\Execution\Script;

/**
 * @internal
 */
#[Package('core')]
class RepositoryWriterFacadeHookFactory extends HookServiceFactory
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $registry,
        private readonly AppContextCreator $appContextCreator,
        private readonly SyncService $syncService
    ) {
    }

    public function factory(Hook $hook, Script $script): RepositoryWriterFacade
    {
        return new RepositoryWriterFacade(
            $this->registry,
            $this->syncService,
            $this->appContextCreator->getAppContext($hook, $script)
        );
    }

    public function getName(): string
    {
        return 'writer';
    }
}
