<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class CustomerCreatedByAdminRule extends Rule
{
    final public const RULE_NAME = 'customerCreatedByAdmin';

    /**
     * @internal
     */
    public function __construct(protected bool $shouldCustomerBeCreatedByAdmin = true)
    {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        if (!$customer = $scope->getSalesChannelContext()->getCustomer()) {
            return false;
        }

        return $this->shouldCustomerBeCreatedByAdmin === (bool) $customer->getCreatedById();
    }

    public function getConstraints(): array
    {
        return [
            'shouldCustomerBeCreatedByAdmin' => RuleConstraints::bool(true),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->booleanField('shouldCustomerBeCreatedByAdmin');
    }
}
