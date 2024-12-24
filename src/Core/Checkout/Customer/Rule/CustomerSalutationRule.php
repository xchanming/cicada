<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\System\Salutation\SalutationDefinition;

#[Package('services-settings')]
class CustomerSalutationRule extends Rule
{
    final public const RULE_NAME = 'customerSalutation';

    /**
     * @internal
     *
     * @param list<string>|null $salutationIds
     */
    public function __construct(
        public string $operator = self::OPERATOR_EQ,
        public ?array $salutationIds = null
    ) {
        parent::__construct();
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => RuleConstraints::uuidOperators(true),
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['salutationIds'] = RuleConstraints::uuids();

        return $constraints;
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        if (!$customer = $scope->getSalesChannelContext()->getCustomer()) {
            return RuleComparison::isNegativeOperator($this->operator);
        }
        $salutation = $customer->getSalutation();

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $salutation === null;
        }

        if ($salutation === null) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        return RuleComparison::uuids([$salutation->getId()], $this->salutationIds, $this->operator);
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, true, true)
            ->entitySelectField('salutationIds', SalutationDefinition::ENTITY_NAME, true, ['labelProperty' => 'displayName']);
    }
}
