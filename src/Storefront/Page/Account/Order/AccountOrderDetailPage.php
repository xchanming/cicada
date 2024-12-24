<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Account\Order;

use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Log\Package;
use Cicada\Storefront\Page\Page;

#[Package('checkout')]
class AccountOrderDetailPage extends Page
{
    /**
     * @var OrderEntity
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $order;

    /**
     * @var OrderLineItemCollection|null
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $lineItems;

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function setOrder(OrderEntity $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getLineItems(): ?OrderLineItemCollection
    {
        return $this->lineItems;
    }

    public function setLineItems(?OrderLineItemCollection $lineItems): self
    {
        $this->lineItems = $lineItems;

        return $this;
    }
}
