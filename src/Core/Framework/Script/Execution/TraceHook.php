<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Execution;

use Cicada\Core\Framework\Log\Package;

/**
 * Only to be used by "dummy" hooks for the sole purpose of tracing
 *
 * @internal
 */
#[Package('core')]
abstract class TraceHook extends Hook
{
}
