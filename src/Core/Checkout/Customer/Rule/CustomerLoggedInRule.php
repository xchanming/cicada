<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class CustomerLoggedInRule extends Rule
{
    final public const RULE_NAME = 'customerLoggedIn';

    /**
     * @internal
     */
    public function __construct(protected bool $isLoggedIn = false)
    {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        $customer = $scope->getSalesChannelContext()->getCustomer();

        $loggedIn = $customer !== null;

        return $this->isLoggedIn === $loggedIn;
    }

    public function getConstraints(): array
    {
        return [
            'isLoggedIn' => RuleConstraints::bool(true),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->booleanField('isLoggedIn');
    }
}
