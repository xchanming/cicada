<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use PHPStan\Analyser\Scope;
use Cicada\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('core')]
trait InTestClassTrait
{
    protected function isInTestClass(Scope $scope): bool
    {
        if (!$scope->isInClass()) {
            return false;
        }

        $className = $scope->getClassReflection()->getNativeReflection()->getName();

        return str_contains(\strtolower($className), 'test') || \str_contains(\strtolower($className), 'tests');
    }
}
