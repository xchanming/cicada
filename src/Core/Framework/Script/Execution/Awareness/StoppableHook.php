<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Execution\Awareness;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
interface StoppableHook
{
    public function stopPropagation(): void;

    public function isPropagationStopped(): bool;
}
