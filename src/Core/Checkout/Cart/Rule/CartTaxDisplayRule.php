<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class CartTaxDisplayRule extends Rule
{
    final public const RULE_NAME = 'cartTaxDisplay';

    /**
     * @internal
     */
    public function __construct(protected string $taxDisplay = CartPrice::TAX_STATE_GROSS)
    {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        return $this->taxDisplay === $scope->getSalesChannelContext()->getTaxState();
    }

    public function getConstraints(): array
    {
        return [
            'taxDisplay' => RuleConstraints::string(),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->selectField('taxDisplay', [CartPrice::TAX_STATE_GROSS, CartPrice::TAX_STATE_NET]);
    }
}
