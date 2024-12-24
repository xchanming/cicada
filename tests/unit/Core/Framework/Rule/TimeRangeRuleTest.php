<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Rule\TimeRangeRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(TimeRangeRule::class)]
class TimeRangeRuleTest extends TestCase
{
    public function testIfOnSameDayInTimeRangeMatches(): void
    {
        $rule = new TimeRangeRule();

        $rule->assign(['fromTime' => '00:00', 'toTime' => '12:00']);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable('12:00'));

        $match = $rule->match($ruleScope);

        static::assertTrue($match);
    }

    public function testIfOnSameDayOutOfTimeRangeMatches(): void
    {
        $rule = new TimeRangeRule();

        $rule->assign(['fromTime' => '00:00', 'toTime' => '12:00']);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable('12:01'));

        $match = $rule->match($ruleScope);

        static::assertFalse($match);
    }

    public function testIfToTimeIsSmallerThanFromTimeMatchesCorrect(): void
    {
        $rule = new TimeRangeRule();

        $rule->assign(['fromTime' => '23:00', 'toTime' => '22:00']);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable('23:00'));

        $match = $rule->match($ruleScope);

        static::assertFalse($match);
    }

    public function testBeforeEdgeToNextDayConditionMatchesCorrect(): void
    {
        $rule = new TimeRangeRule();

        $rule->assign(['fromTime' => '23:00', 'toTime' => '22:00']);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable('22:59'));

        $match = $rule->match($ruleScope);

        static::assertFalse($match);
    }

    public function testOnNextDayConditionMatchesCorrect(): void
    {
        $rule = new TimeRangeRule();

        $rule->assign(['fromTime' => '23:00', 'toTime' => '22:00']);

        $ruleScope = $this->createMock(RuleScope::class);
        $ruleScope->method('getCurrentTime')->willReturn(new \DateTimeImmutable('02:46'));

        $match = $rule->match($ruleScope);

        static::assertTrue($match);
    }
}
