<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\DaysSinceRule;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class DaysSinceLastOrderRule extends DaysSinceRule
{
    final public const RULE_NAME = 'customerDaysSinceLastOrder';

    public int $count;

    protected function getDate(RuleScope $scope): ?\DateTimeInterface
    {
        return $scope->getSalesChannelContext()->getCustomer()?->getLastOrderDate();
    }

    protected function supportsScope(RuleScope $scope): bool
    {
        return $scope instanceof CheckoutRuleScope;
    }
}
