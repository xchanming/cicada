<?php declare(strict_types=1);

namespace Cicada\Core\Profiling\Routing;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RouteScopeWhitelistInterface;
use Cicada\Core\Profiling\Controller\ProfilerController;

#[Package('core')]
class ProfilerWhitelist implements RouteScopeWhitelistInterface
{
    public function applies(string $controllerClass): bool
    {
        return $controllerClass === ProfilerController::class;
    }
}
