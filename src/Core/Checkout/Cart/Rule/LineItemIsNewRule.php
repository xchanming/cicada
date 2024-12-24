<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class LineItemIsNewRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemIsNew';

    /**
     * @internal
     */
    public function __construct(protected bool $isNew = false)
    {
        parent::__construct();
    }

    /**
     * @throws CartException
     */
    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->matchLineItemIsNew($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $lineItem) {
            if ($this->matchLineItemIsNew($lineItem)) {
                return true;
            }
        }

        return false;
    }

    public function getConstraints(): array
    {
        return [
            'isNew' => RuleConstraints::bool(),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->booleanField('isNew');
    }

    /**
     * @throws CartException
     */
    private function matchLineItemIsNew(LineItem $lineItem): bool
    {
        return (bool) $lineItem->getPayloadValue('isNew') === $this->isNew;
    }
}
