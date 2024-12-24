<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\Tests;

use Cicada\Core\Framework\Log\Package;
use PHPStan\Reflection\ClassReflection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('core')]
class TestRuleHelper
{
    public static function isTestClass(ClassReflection $class): bool
    {
        foreach ($class->getParents() as $parent) {
            if ($parent->getName() === TestCase::class) {
                return true;
            }
        }

        return false;
    }

    public static function isUnitTestClass(ClassReflection $class): bool
    {
        if (!static::isTestClass($class)) {
            return false;
        }

        $unitTestNamespaces = [
            'Cicada\\Tests\\Unit\\',
            'Cicada\\Tests\\Migration\\',

            'Cicada\\Commercial\\Tests\\Unit\\',
            'Cicada\\Commercial\\Migration\\Test\\',

            'Swag\\SaasRufus\\Test\\Migration\\',
            'Swag\\SaasRufus\\Tests\\Unit\\',
        ];

        foreach ($unitTestNamespaces as $unitTestNamespace) {
            if (\str_contains($class->getName(), $unitTestNamespace)) {
                return true;
            }
        }

        return false;
    }
}
