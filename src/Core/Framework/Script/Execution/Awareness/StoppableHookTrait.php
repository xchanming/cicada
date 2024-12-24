<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Execution\Awareness;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
trait StoppableHookTrait
{
    protected bool $isPropagationStopped = false;

    public function stopPropagation(): void
    {
        $this->isPropagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }
}
