<?php declare(strict_types=1);

namespace Cicada\Core\Framework\SystemCheck;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\SystemCheck\Check\Category;
use Cicada\Core\Framework\SystemCheck\Check\Result;
use Cicada\Core\Framework\SystemCheck\Check\SystemCheckExecutionContext;

#[Package('core')]
abstract class BaseCheck
{
    abstract public function run(): Result;

    abstract public function category(): Category;

    abstract public function name(): string;

    public function allowedToRunIn(SystemCheckExecutionContext $context): bool
    {
        return \in_array($context, $this->allowedSystemCheckExecutionContexts(), true);
    }

    /**
     * @return array<SystemCheckExecutionContext>
     */
    abstract protected function allowedSystemCheckExecutionContexts(): array;
}
