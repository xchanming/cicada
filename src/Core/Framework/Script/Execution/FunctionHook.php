<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Execution;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal only rely on the concrete implementations
 */
#[Package('framework')]
abstract class FunctionHook extends Hook
{
    /**
     * Returns the name of the function.
     */
    abstract public function getFunctionName(): string;
}
