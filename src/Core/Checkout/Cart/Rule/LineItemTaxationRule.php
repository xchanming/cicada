<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\Tax\TaxDefinition;

#[Package('fundamentals@after-sales')]
class LineItemTaxationRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemTaxation';

    /**
     * @internal
     *
     * @param list<string> $taxIds
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected array $taxIds = []
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->matchesOneOfTaxations($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $lineItem) {
            if ($this->matchesOneOfTaxations($lineItem)) {
                return true;
            }
        }

        return false;
    }

    public function getConstraints(): array
    {
        return [
            'taxIds' => RuleConstraints::uuids(),
            'operator' => RuleConstraints::uuidOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('taxIds', TaxDefinition::ENTITY_NAME, true);
    }

    /**
     * @throws UnsupportedOperatorException
     * @throws CartException
     */
    private function matchesOneOfTaxations(LineItem $lineItem): bool
    {
        return RuleComparison::uuids([$lineItem->getPayloadValue('taxId')], $this->taxIds, $this->operator);
    }
}
