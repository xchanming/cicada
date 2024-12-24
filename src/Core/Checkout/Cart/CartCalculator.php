<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Profiling\Profiler;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 * This class is used to recalculate a modified shopping cart. For this it uses the CartRuleLoader class.
 * The rule loader recalculates the cart and validates the current rules.
 */
#[Package('checkout')]
class CartCalculator
{
    public function __construct(
        private readonly CartRuleLoader $cartRuleLoader,
        private readonly CartContextHasher $cartContextHasher
    ) {
    }

    public function calculate(Cart $cart, SalesChannelContext $context): Cart
    {
        return Profiler::trace('cart-calculation', function () use ($cart, $context) {
            // validate cart against the context rules
            $cart = $this->cartRuleLoader
                ->loadByCart($context, $cart, new CartBehavior($context->getPermissions()))
                ->getCart();

            $cart->setHash($this->cartContextHasher->generate($cart, $context));

            $cart->markUnmodified();
            foreach ($cart->getLineItems()->getFlat() as $lineItem) {
                $lineItem->markUnmodified();
            }

            return $cart;
        });
    }
}
