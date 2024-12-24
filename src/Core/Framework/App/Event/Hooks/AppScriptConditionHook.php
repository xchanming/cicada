<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\Event\Hooks;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Execution\TraceHook;

/**
 * @internal
 */
#[Package('core')]
class AppScriptConditionHook extends TraceHook
{
    public static function getServiceIds(): array
    {
        return [];
    }

    public function getName(): string
    {
        return 'rule-conditions';
    }
}
