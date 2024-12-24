<?php declare(strict_types=1);

namespace Cicada\Tests\DevOps\Core\DevOps\StaticAnalyse\PHPStan\Rules\Tests;

use Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules\Tests\TestRuleHelper;
use PHPStan\Reflection\ClassReflection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TestRuleHelper::class)]
class TestRuleHelperTest extends TestCase
{
    #[DataProvider('classProvider')]
    public function testIsTestClass(string $className, bool $extendsTestCase, bool $isTestClass, bool $isUnitTestClass): void
    {
        $classReflection = $this->createMock(ClassReflection::class);
        $classReflection
            ->method('getName')
            ->willReturn($className);

        if ($extendsTestCase) {
            $parentClass = $this->createMock(ClassReflection::class);
            $parentClass
                ->method('getName')
                ->willReturn(TestCase::class);

            $classReflection
                ->method('getParents')
                ->willReturn([$parentClass]);
        }

        static::assertSame($isTestClass, TestRuleHelper::isTestClass($classReflection));
        static::assertSame($isUnitTestClass, TestRuleHelper::isUnitTestClass($classReflection));
    }

    public static function classProvider(): \Generator
    {
        yield [
            'className' => 'Cicada\Some\NonTestClass',
            'extendsTestCase' => false,
            'isTestClass' => false,
            'isUnitTestClass' => false,
        ];

        yield [
            'className' => 'Cicada\Commercial\Tests\SomeTestClass',
            'extendsTestCase' => true,
            'isTestClass' => true,
            'isUnitTestClass' => false,
        ];

        yield [
            'className' => 'Cicada\Tests\SomeTestClass',
            'extendsTestCase' => true,
            'isTestClass' => true,
            'isUnitTestClass' => false,
        ];

        yield [
            'className' => 'Cicada\Tests\Unit\SomeTestClass',
            'extendsTestCase' => true,
            'isTestClass' => true,
            'isUnitTestClass' => true,
        ];

        yield [
            'className' => 'Cicada\Tests\Integration\SomeTestClass',
            'extendsTestCase' => true,
            'isTestClass' => true,
            'isUnitTestClass' => false,
        ];

        yield [
            'className' => 'Cicada\Tests\SomeNonTestClass',
            'extendsTestCase' => false,
            'isTestClass' => false,
            'isUnitTestClass' => false,
        ];
    }
}
