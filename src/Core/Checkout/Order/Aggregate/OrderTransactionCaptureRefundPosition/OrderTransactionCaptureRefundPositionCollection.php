<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCaptureRefundPosition;

use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<OrderTransactionCaptureRefundPositionEntity>
 */
#[Package('checkout')]
class OrderTransactionCaptureRefundPositionCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'order_transaction_capture_refund_position_collection';
    }

    protected function getExpectedClass(): string
    {
        return OrderTransactionCaptureRefundPositionEntity::class;
    }
}
