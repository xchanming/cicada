<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule\Container;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Rule\Container\Container;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfType;
use Cicada\Core\Test\Stub\Rule\FalseRule;
use Cicada\Core\Test\Stub\Rule\TrueRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(AndRule::class)]
#[CoversClass(Container::class)]
class AndRuleTest extends TestCase
{
    #[DataProvider('cases')]
    public function testRuleLogic(AndRule $rule, bool $matching): void
    {
        $scope = $this->createMock(RuleScope::class);
        static::assertSame($matching, $rule->match($scope));
    }

    public function testAndRuleNameIsStillTheSame(): void
    {
        static::assertSame('andContainer', (new AndRule())->getName());
    }

    public function testICanAddRulesAfterwards(): void
    {
        $rule = new AndRule([new TrueRule()]);
        $rule->addRule(new TrueRule());

        static::assertEquals([new TrueRule(), new TrueRule()], $rule->getRules());

        $rule->setRules([new FalseRule()]);
        static::assertEquals([new FalseRule()], $rule->getRules());
    }

    public function testConstraintsAreStillTheSame(): void
    {
        static::assertEquals(
            ['rules' => [new ArrayOfType(Rule::class)]],
            (new AndRule())->getConstraints()
        );
    }

    public static function cases(): \Generator
    {
        yield 'Test with single matching rule' => [
            new AndRule([new TrueRule()]),
            true,
        ];

        yield 'Test with multiple matching rule' => [
            new AndRule([
                new TrueRule(),
                new TrueRule(),
            ]),
            true,
        ];

        yield 'Test with single not matching rule' => [
            new AndRule([new FalseRule()]),
            false,
        ];

        yield 'Test with multiple not matching rule' => [
            new AndRule([
                new TrueRule(),
                new FalseRule(),
            ]),
            false,
        ];

        yield 'Test with matching and not matching rule' => [
            new AndRule([
                new TrueRule(),
                new FalseRule(),
            ]),
            false,
        ];
    }
}
