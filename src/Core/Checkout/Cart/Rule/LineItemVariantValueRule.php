<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class LineItemVariantValueRule extends Rule
{
    public const RULE_NAME = 'cartLineItemVariantValue';

    /**
     * @internal
     *
     * @param list<string>|null $identifiers
     */
    public function __construct(
        public string $operator = Rule::OPERATOR_EQ,
        public ?array $identifiers = null
    ) {
        parent::__construct();
    }

    public function getConstraints(): array
    {
        return [
            'operator' => RuleConstraints::uuidOperators(false),
            'identifiers' => RuleConstraints::uuids(),
        ];
    }

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->matchLineItem($scope->getLineItem());
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->filterGoodsFlat() as $item) {
            if ($this->matchLineItem($item)) {
                return true;
            }
        }

        return false;
    }

    public function matchLineItem(LineItem $lineItem): bool
    {
        /**
         * @var list<string> $value
         */
        $value = $lineItem->getPayloadValue('optionIds') ?? [];

        return RuleComparison::uuids(
            $value,
            $this->identifiers,
            $this->operator
        );
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('identifiers', PropertyGroupOptionDefinition::ENTITY_NAME, true);
    }
}
