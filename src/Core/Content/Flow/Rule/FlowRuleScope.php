<?php declare(strict_types=1);

namespace Cicada\Core\Content\Flow\Rule;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('services-settings')]
class FlowRuleScope extends CartRuleScope
{
    public function __construct(
        private readonly OrderEntity $order,
        Cart $cart,
        SalesChannelContext $context
    ) {
        parent::__construct($cart, $context);
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }
}
