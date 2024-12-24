<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Order;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class OrderConvertedEvent extends NestedEvent
{
    private Cart $convertedCart;

    public function __construct(
        private OrderEntity $order,
        private Cart $cart,
        private Context $context
    ) {
        $this->convertedCart = clone $cart;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getConvertedCart(): Cart
    {
        return $this->convertedCart;
    }

    public function setConvertedCart(Cart $convertedCart): void
    {
        $this->convertedCart = $convertedCart;
    }
}
