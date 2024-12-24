<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\Rule;

use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\Framework\Rule\RuleScope;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

#[Package('services-settings')]
class DifferentAddressesRule extends Rule
{
    final public const RULE_NAME = 'customerDifferentAddresses';

    /**
     * @var bool
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $isDifferent;

    /**
     * @internal
     */
    public function __construct(bool $isDifferent = true)
    {
        parent::__construct();
        $this->isDifferent = $isDifferent;
    }

    public function match(RuleScope $scope): bool
    {
        if (!$scope instanceof CheckoutRuleScope) {
            return false;
        }

        if (!$customer = $scope->getSalesChannelContext()->getCustomer()) {
            return false;
        }

        if (!$billingAddress = $customer->getActiveBillingAddress()) {
            return false;
        }

        if (!$shippingAddress = $customer->getActiveShippingAddress()) {
            return false;
        }

        if ($this->isDifferent) {
            return $billingAddress->getId() !== $shippingAddress->getId();
        }

        return $billingAddress->getId() === $shippingAddress->getId();
    }

    public function getConstraints(): array
    {
        return [
            'isDifferent' => [new NotNull(), new Type('bool')],
        ];
    }

    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())
            ->booleanField('isDifferent');
    }
}
