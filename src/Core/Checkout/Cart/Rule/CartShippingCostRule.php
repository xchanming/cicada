<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class CartShippingCostRule extends Rule
{
    public const RULE_NAME = 'cartShippingCost';

    protected ?float $cartShippingCost;

    protected string $operator;

    /**
     * @internal
     */
    public function __construct(
        string $operator = self::OPERATOR_EQ,
        ?float $cartShippingCost = null
    ) {
        parent::__construct();

        $this->operator = $operator;
        $this->cartShippingCost = $cartShippingCost;
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        return RuleComparison::numeric($this->fetchShippingCosts($scope->getCart()), $this->cartShippingCost, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'cartShippingCost' => RuleConstraints::float(),
            'operator' => RuleConstraints::numericOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_NUMBER)
            ->numberField('cartShippingCost');
    }

    private function fetchShippingCosts(Cart $cart): float
    {
        return $cart->getShippingCosts()->getTotalPrice();
    }
}
