<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleConstraints;
use Cicada\Core\Framework\Rule\RuleScope;

#[Package('services-settings')]
class CartHasDeliveryFreeItemRule extends Rule
{
    final public const RULE_NAME = 'cartHasDeliveryFreeItem';

    /**
     * @internal
     */
    public function __construct(protected bool $allowed = true)
    {
        parent::__construct();
    }

    public function match(RuleScope $scope): bool
    {
        if ($scope instanceof LineItemScope) {
            return $this->isFreeDeliveryItem($scope->getLineItem()) === $this->allowed;
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        $hasFreeDeliveryItems = $this->hasFreeDeliveryItems($scope->getCart()->getLineItems());

        return $hasFreeDeliveryItems === $this->allowed;
    }

    public function getConstraints(): array
    {
        return [
            'allowed' => RuleConstraints::bool(),
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->booleanField('allowed');
    }

    private function hasFreeDeliveryItems(LineItemCollection $lineItems): bool
    {
        foreach ($lineItems->filterGoodsFlat() as $lineItem) {
            if ($this->isFreeDeliveryItem($lineItem) === true) {
                return true;
            }
        }

        return false;
    }

    private function isFreeDeliveryItem(LineItem $lineItem): bool
    {
        $deliveryInformation = $lineItem->getDeliveryInformation();
        if ($deliveryInformation === null) {
            return false;
        }

        return $deliveryInformation->getFreeDelivery();
    }
}
