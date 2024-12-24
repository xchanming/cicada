<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event\Hooks;

use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryWriterFacadeHookFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\Hook;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;

/**
 * @internal only rely on the concrete hook implementations
 */
#[Package('core')]
abstract class AppLifecycleHook extends Hook
{
    public static function getServiceIds(): array
    {
        return [
            RepositoryFacadeHookFactory::class,
            SystemConfigFacadeHookFactory::class,
            RepositoryWriterFacadeHookFactory::class,
        ];
    }
}
