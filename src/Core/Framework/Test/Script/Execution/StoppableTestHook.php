<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Script\Execution;

use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHook;
use Cicada\Core\Framework\Script\Execution\Awareness\StoppableHookTrait;

/**
 * @internal
 */
class StoppableTestHook extends TestHook implements StoppableHook
{
    use StoppableHookTrait;
}
