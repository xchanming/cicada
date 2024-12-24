<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class PaymentMethodRule extends Rule
{
    final public const RULE_NAME = 'paymentMethod';

    /**
     * @param list<string> $paymentMethodIds
     *
     * @internal
     */
    public function __construct(
        protected string $operator = Rule::OPERATOR_EQ,
        protected array $paymentMethodIds = []
    ) {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        return RuleComparison::uuids([$scope->getSalesChannelContext()->getPaymentMethod()->getId()], $this->paymentMethodIds, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'paymentMethodIds' => RuleConstraints::uuids(),
            'operator' => RuleConstraints::uuidOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('paymentMethodIds', PaymentMethodDefinition::ENTITY_NAME, true);
    }
}
