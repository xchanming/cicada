<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('checkout')]
class BeforeCartMergeEvent extends Event implements CicadaSalesChannelEvent
{
    /**
     * @internal
     */
    public function __construct(
        protected Cart $customerCart,
        protected Cart $guestCart,
        protected LineItemCollection $mergeableLineItems,
        protected SalesChannelContext $context
    ) {
    }

    public function getCustomerCart(): Cart
    {
        return $this->customerCart;
    }

    public function getGuestCart(): Cart
    {
        return $this->guestCart;
    }

    public function getMergeableLineItems(): LineItemCollection
    {
        return $this->mergeableLineItems;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }
}
