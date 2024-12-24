<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('services-settings')]
class CartRuleScope extends CheckoutRuleScope
{
    public function __construct(
        protected Cart $cart,
        SalesChannelContext $context
    ) {
        parent::__construct($context);
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }
}
