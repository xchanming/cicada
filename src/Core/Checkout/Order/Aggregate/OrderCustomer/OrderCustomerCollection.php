<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderCustomer;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<OrderCustomerEntity>
 */
#[Package('checkout')]
class OrderCustomerCollection extends EntityCollection
{
    /**
     * @return array<string>
     */
    public function getCustomerIds(): array
    {
        return $this->fmap(fn (OrderCustomerEntity $orderCustomer) => $orderCustomer->getCustomerId());
    }

    public function filterByCustomerId(string $id): self
    {
        return $this->filter(fn (OrderCustomerEntity $orderCustomer) => $orderCustomer->getCustomerId() === $id);
    }

    public function getCustomers(): CustomerCollection
    {
        return new CustomerCollection(
            $this->fmap(fn (OrderCustomerEntity $orderCustomer) => $orderCustomer->getCustomer())
        );
    }

    public function getLastOrderDate(): ?\DateTimeInterface
    {
        $lastOrderDate = null;

        foreach ($this->getOrders() as $order) {
            if (!$lastOrderDate || $order->getOrderDate() < $lastOrderDate) {
                $lastOrderDate = $order->getOrderDate();
            }
        }

        return $lastOrderDate;
    }

    public function getOrders(): OrderCollection
    {
        $orders = new OrderCollection();
        foreach ($this->getElements() as $orderCustomer) {
            if ($orderCustomer->getOrder() === null) {
                continue;
            }
            $orders->add($orderCustomer->getOrder());
        }

        return $orders;
    }

    public function getApiAlias(): string
    {
        return 'order_customer_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderCustomerEntity::class;
    }
}
