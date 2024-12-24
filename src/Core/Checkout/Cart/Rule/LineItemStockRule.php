<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\Exception\UnsupportedValueException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class LineItemStockRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemStock';

    /**
     * @internal
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?int $stock = null
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->matchStock($scope->getLineItem());
        }

        if ($scope instanceof CartRuleScope) {
            return $this->matchStockFromCollection($scope->getCart()->getLineItems()->filterGoodsFlat());
        }

        return false;
    }

    public function getConstraints(): array
    {
        return [
            'operator' => RuleConstraints::numericOperators(false),
            'stock' => RuleConstraints::int(),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_NUMBER)
            ->intField('stock');
    }

    /**
     * @throws UnsupportedOperatorException|UnsupportedValueException
     */
    private function matchStock(LineItem $lineItem): bool
    {
        if ($this->stock === null) {
            throw new UnsupportedValueException(\gettype($this->stock), self::class);
        }

        $deliveryInformation = $lineItem->getDeliveryInformation();

        if (!$deliveryInformation instanceof DeliveryInformation) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        return RuleComparison::numeric($deliveryInformation->getStock(), $this->stock, $this->operator);
    }

    /**
     * @param LineItem[] $lineItems
     */
    private function matchStockFromCollection(array $lineItems): bool
    {
        foreach ($lineItems as $lineItem) {
            if ($this->matchStock($lineItem)) {
                return true;
            }
        }

        return false;
    }
}
