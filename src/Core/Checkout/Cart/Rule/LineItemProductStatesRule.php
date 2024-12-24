<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraint;

#[Package('services-settings')]
class LineItemProductStatesRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemProductStates';

    protected string $productState;

    protected string $operator;

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->lineItemMatches($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $lineItem) {
            if ($this->lineItemMatches($lineItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array<int, Constraint>>
     */
    public function getConstraints(): array
    {
        return [
            'operator' => RuleConstraints::stringOperators(false),
            'productState' => RuleConstraints::choice([
                State::IS_PHYSICAL,
                State::IS_DOWNLOAD,
            ]),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING)
            ->selectField('productState', [
                State::IS_PHYSICAL,
                State::IS_DOWNLOAD,
            ]);
    }

    private function lineItemMatches(LineItem $lineItem): bool
    {
        return RuleComparison::stringArray($this->productState, array_values($lineItem->getStates()), $this->operator);
    }
}
