<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Framework\Rule\Fixture;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\DaysSinceRule;
use Cicada\Core\Framework\Rule\RuleScope;

/**
 * @internal
 */
#[Package('services-settings')]
class DaysSinceRuleFixture extends DaysSinceRule
{
    final public const RULE_NAME = 'fixtureDaysSince';

    protected function getDate(RuleScope $scope): ?\DateTimeInterface
    {
        return null;
    }

    protected function supportsScope(RuleScope $scope): bool
    {
        return false;
    }
}
