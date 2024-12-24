<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class AfterLineItemAddedEvent implements CicadaSalesChannelEvent, CartEvent
{
    /**
     * @var LineItem[]
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $lineItems;

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
     * @param LineItem[] $lineItems
     */
    public function __construct(
        array $lineItems,
        Cart $cart,
        SalesChannelContext $salesChannelContext
    ) {
        $this->lineItems = $lineItems;
        $this->cart = $cart;
        $this->salesChannelContext = $salesChannelContext;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getCart(): Cart
    {
        return $this->cart;
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
