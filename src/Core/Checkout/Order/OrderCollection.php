<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order;

use Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Currency\CurrencyCollection;
use Cicada\Core\System\SalesChannel\SalesChannelCollection;

/**
 * @extends EntityCollection<OrderEntity>
 */
#[Package('checkout')]
class OrderCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getCurrencyIds(): array
    {
        return $this->fmap(fn (OrderEntity $order) => $order->getCurrencyId());
    }

    public function filterByCurrencyId(string $id): self
    {
        return $this->filter(fn (OrderEntity $order) => $order->getCurrencyId() === $id);
    }

    /**
     * @return array<string>
     */
    public function getSalesChannelIs(): array
    {
        return $this->fmap(fn (OrderEntity $order) => $order->getSalesChannelId());
    }

    public function filterBySalesChannelId(string $id): self
    {
        return $this->filter(fn (OrderEntity $order) => $order->getSalesChannelId() === $id);
    }

    public function getOrderCustomers(): OrderCustomerCollection
    {
        return new OrderCustomerCollection(
            $this->fmap(fn (OrderEntity $order) => $order->getOrderCustomer())
        );
    }

    public function getCurrencies(): CurrencyCollection
    {
        return new CurrencyCollection(
            $this->fmap(fn (OrderEntity $order) => $order->getCurrency())
        );
    }

    public function getSalesChannels(): SalesChannelCollection
    {
        return new SalesChannelCollection(
            $this->fmap(fn (OrderEntity $order) => $order->getSalesChannel())
        );
    }

    public function getBillingAddress(): OrderAddressCollection
    {
        return new OrderAddressCollection(
            $this->fmap(fn (OrderEntity $order) => $order->getAddresses())
        );
    }

    public function getApiAlias(): string
    {
        return 'order_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderEntity::class;
    }
}
