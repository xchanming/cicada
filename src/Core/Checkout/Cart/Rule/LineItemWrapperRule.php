<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\Container;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

#[Package('services-settings')]
class LineItemWrapperRule extends Rule
{
    final public const RULE_NAME = 'cartLineItemWrapper';

    protected Container $container;

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }
        if ($scope instanceof LineItemScope) {
            return $this->container->match($scope);
        }

        if (!$scope instanceof CartRuleScope) {
            return false;
        }

        foreach ($scope->getCart()->getLineItems()->getFlat() as $lineItem) {
            $context = new LineItemScope($lineItem, $scope->getSalesChannelContext());
            $match = $this->container->match($context);
            if ($match) {
                return true;
            }
        }

        return false;
    }

    public function getConstraints(): array
    {
        return [
            'container' => [new NotBlank(), new Type(Container::class)],
        ];
    }
}
