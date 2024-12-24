<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Rule;

use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Exception\ScriptExecutionFailedException;
use Cicada\Core\Framework\Script\ScriptException;

#[Package('services-settings')]
class RuleException extends HttpException
{
    public static function scriptExecutionFailed(string $hook, string $scriptName, \Throwable $previous): ScriptException
    {
        // use own exception class so it can be catched properly
        return new ScriptExecutionFailedException($hook, $scriptName, $previous);
    }
}
