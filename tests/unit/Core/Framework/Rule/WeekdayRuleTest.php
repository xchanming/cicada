<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Rule\WeekdayRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(WeekdayRule::class)]
class WeekdayRuleTest extends TestCase
{
    public function testMatchForCurrentDay(): void
    {
        $rule = new WeekdayRule();
        $rule->assign([
            'operator' => WeekdayRule::OPERATOR_EQ,
            'dayOfWeek' => (int) date('N'),
        ]);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable());
        $match = $rule->match($ruleScope);

        static::assertTrue($match);
    }

    public function testMatchForYesterday(): void
    {
        $rule = new WeekdayRule();
        $rule->assign([
            'operator' => WeekdayRule::OPERATOR_EQ,
            'dayOfWeek' => (int) (new \DateTime())->modify('-1 day')->format('N'),
        ]);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable());
        $match = $rule->match($ruleScope);

        static::assertFalse($match);
    }

    public function testMatchWithNotEqualsOperator(): void
    {
        $rule = new WeekdayRule();
        $rule->assign([
            'operator' => WeekdayRule::OPERATOR_NEQ,
            'dayOfWeek' => (int) date('N'),
        ]);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable());
        $match = $rule->match($ruleScope);

        static::assertFalse($match);
    }
}
