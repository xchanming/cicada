<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class AfterLineItemQuantityChangedEvent implements CicadaSalesChannelEvent, CartEvent
{
    /**
     * @var array<array<string, mixed>>
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $items;

    /**
     * @var Cart
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $cart;

    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelContext;

    /**
     * @param array<array<string, mixed>> $items
     */
    public function __construct(
        Cart $cart,
        array $items,
        SalesChannelContext $salesChannelContext
    ) {
        $this->cart = $cart;
        $this->items = $items;
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
