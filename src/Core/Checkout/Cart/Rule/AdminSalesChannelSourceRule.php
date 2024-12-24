<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class AdminSalesChannelSourceRule extends Rule
{
    final public const RULE_NAME = 'adminSalesChannelSource';

    /**
     * @internal
     */
    public function __construct(protected bool $hasAdminSalesChannelSource = false)
    {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        $hasAdminSalesChannelSource = $scope->getContext()->getSource() instanceof AdminSalesChannelApiSource;

        if ($this->hasAdminSalesChannelSource) {
            return $hasAdminSalesChannelSource;
        }

        return !$hasAdminSalesChannelSource;
    }

    public function getConstraints(): array
    {
        return [
            'hasAdminSalesChannelSource' => RuleConstraints::bool(),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())->booleanField('hasAdminSalesChannelSource');
    }
}
