<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Dispatching;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal not intended for decoration or replacement
 */
#[Package('after-sales')]
abstract class AbstractFlowLoader
{
    abstract public function load(): array;
}
