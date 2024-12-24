<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartContextHashStruct;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CartContextHashEvent extends Event implements CicadaSalesChannelEvent, CartEvent
{
    public function __construct(
        protected readonly SalesChannelContext $salesChannelContext,
        protected readonly Cart $cart,
        protected CartContextHashStruct $hashStruct
    ) {
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getHashStruct(): CartContextHashStruct
    {
        return $this->hashStruct;
    }

    public function setHashStruct(CartContextHashStruct $hashStruct): void
    {
        $this->hashStruct = $hashStruct;
    }
}
