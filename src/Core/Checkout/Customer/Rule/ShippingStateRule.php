<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleScope;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfUuid;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateDefinition;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Package('services-settings')]
class ShippingStateRule extends Rule
{
    final public const RULE_NAME = 'customerShippingState';

    /**
     * @internal
     *
     * @param list<string>|null $stateIds
     */
    public function __construct(
        protected string $operator = self::OPERATOR_EQ,
        protected ?array $stateIds = null
    ) {
        parent::__construct();
    }

    /**
     * @throws UnsupportedOperatorException
     */
    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        if (!$state = $scope->getSalesChannelContext()->getShippingLocation()->getState()) {
            return RuleComparison::isNegativeOperator($this->operator);
        }

        $stateId = $state->getId();
        $parameter = [$stateId];
        if ($stateId === '') {
            $parameter = [];
        }

        return RuleComparison::uuids($parameter, $this->stateIds, $this->operator);
    }

    public function getConstraints(): array
    {
        $constraints = [
            'operator' => [
                new NotBlank(),
                new Choice([self::OPERATOR_EQ, self::OPERATOR_NEQ, self::OPERATOR_EMPTY]),
            ],
        ];

        if ($this->operator === self::OPERATOR_EMPTY) {
            return $constraints;
        }

        $constraints['stateIds'] = [new NotBlank(), new ArrayOfUuid()];

        return $constraints;
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, true, true)
            ->entitySelectField('stateIds', CountryStateDefinition::ENTITY_NAME, true);
    }
}
