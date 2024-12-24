<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Script\Execution;

use Cicada\Core\Framework\Script\Execution\DeprecatedHook;

/**
 * @internal
 */
class DeprecatedTestHook extends TestHook implements DeprecatedHook
{
    public static function getDeprecationNotice(): string
    {
        return 'Hook "DeprecatedTestHook" is obviously deprecated.';
    }
}
