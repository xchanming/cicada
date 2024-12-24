<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class CartVerifyPersistEvent extends Event implements CicadaSalesChannelEvent, CartEvent
{
    public function __construct(
        protected SalesChannelContext $context,
        protected Cart $cart,
        protected bool $shouldPersist
    ) {
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function shouldBePersisted(): bool
    {
        return $this->shouldPersist;
    }

    public function setShouldPersist(bool $persist): void
    {
        $this->shouldPersist = $persist;
    }
}
