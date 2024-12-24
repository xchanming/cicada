<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Script\Execution;

use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
interface DeprecatedHook
{
    public static function getDeprecationNotice(): string;
}
