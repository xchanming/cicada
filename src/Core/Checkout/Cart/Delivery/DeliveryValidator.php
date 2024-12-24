<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Delivery;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartValidatorInterface;
use Cicada\Core\Checkout\Cart\Error\ErrorCollection;
use Cicada\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class DeliveryValidator implements CartValidatorInterface
{
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        foreach ($cart->getDeliveries() as $delivery) {
            $shippingMethod = $delivery->getShippingMethod();
            $ruleId = $shippingMethod->getAvailabilityRuleId();

            $matches = \in_array($ruleId, $context->getRuleIds(), true) || $ruleId === null;

            if ($matches && $shippingMethod->getActive()) {
                continue;
            }

            $errors->add(
                new ShippingMethodBlockedError(
                    (string) $shippingMethod->getTranslation('name')
                )
            );
        }
    }
}
