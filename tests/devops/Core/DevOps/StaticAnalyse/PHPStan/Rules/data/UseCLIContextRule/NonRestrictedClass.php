<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\data\UseCLIContextRule;

use Cicada\Core\Framework\Context;

/**
 * @internal
 */
class NonRestrictedClass
{
    public function create(): void
    {
        Context::createDefaultContext();
    }
}
