<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderLineItemDownload;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<OrderLineItemDownloadEntity>
 */
#[Package('checkout')]
class OrderLineItemDownloadCollection extends EntityCollection
{
    public function filterByOrderLineItemId(string $id): self
    {
        return $this->filter(fn (OrderLineItemDownloadEntity $orderLineItemDownloadEntity) => $orderLineItemDownloadEntity->getOrderLineItemId() === $id);
    }

    public function getApiAlias(): string
    {
        return 'order_line_item_download_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderLineItemDownloadEntity::class;
    }
}
