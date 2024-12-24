<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\Migration\AddColumnRule as NewAddColumnRule;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 *
 * @deprecated tag:v6.7.0 - reason:remove-phpstan-rule - Will be removed. Use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\Migration\AddColumnRule instead
 */
#[Package('core')]
class AddColumnRule extends NewAddColumnRule
{
}
