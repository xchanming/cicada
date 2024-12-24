<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order\Aggregate\OrderTransactionCapture;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
final class OrderTransactionCaptureStates
{
    public const STATE_MACHINE = 'order_transaction_capture.state';
    public const STATE_PENDING = 'pending';
    public const STATE_COMPLETED = 'completed';
    public const STATE_FAILED = 'failed';
}
