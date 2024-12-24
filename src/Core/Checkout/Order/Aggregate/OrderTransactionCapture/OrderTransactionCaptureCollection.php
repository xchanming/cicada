<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<OrderTransactionCaptureEntity>
 */
#[Package('checkout')]
class OrderTransactionCaptureCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'order_transaction_capture_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderTransactionCaptureEntity::class;
    }
}
