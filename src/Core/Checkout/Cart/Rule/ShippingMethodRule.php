<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Shipping\ShippingMethodDefinition;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleComparison;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class ShippingMethodRule extends Rule
{
    final public const RULE_NAME = 'shippingMethod';

    /**
     * @var list<string>
     */
    protected array $shippingMethodIds;

    protected string $operator;

    public function match(RuleScope $scope): bool
    {
        return RuleComparison::uuids([$scope->getSalesChannelContext()->getShippingMethod()->getId()], $this->shippingMethodIds, $this->operator);
    }

    public function getConstraints(): array
    {
        return [
            'shippingMethodIds' => RuleConstraints::uuids(),
            'operator' => RuleConstraints::uuidOperators(false),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->operatorSet(RuleConfig::OPERATOR_SET_STRING, false, true)
            ->entitySelectField('shippingMethodIds', ShippingMethodDefinition::ENTITY_NAME, true);
    }
}
