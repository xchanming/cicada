<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderDeliveryPosition;

use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<OrderDeliveryPositionEntity>
 */
#[Package('checkout')]
class OrderDeliveryPositionCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getOrderDeliveryIds(): array
    {
        return $this->fmap(fn (OrderDeliveryPositionEntity $orderDeliveryPosition) => $orderDeliveryPosition->getOrderDeliveryId());
    }

    public function filterByOrderDeliveryId(string $id): self
    {
        return $this->filter(fn (OrderDeliveryPositionEntity $orderDeliveryPosition) => $orderDeliveryPosition->getOrderDeliveryId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getOrderLineItemIds(): array
    {
        return $this->fmap(fn (OrderDeliveryPositionEntity $orderDeliveryPosition) => $orderDeliveryPosition->getOrderLineItemId());
    }

    public function filterByOrderLineItemId(string $id): self
    {
        return $this->filter(fn (OrderDeliveryPositionEntity $orderDeliveryPosition) => $orderDeliveryPosition->getOrderLineItemId() === $id);
    }

    public function getOrderLineItems(): OrderLineItemCollection
    {
        return new OrderLineItemCollection(
            $this->fmap(fn (OrderDeliveryPositionEntity $orderDeliveryPosition) => $orderDeliveryPosition->getOrderLineItem())
        );
    }

    public function getApiAlias(): string
    {
        return 'order_delivery_position_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderDeliveryPositionEntity::class;
    }
}
