<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Order;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
final class OrderStates
{
    public const STATE_MACHINE = 'order.state';
    public const STATE_OPEN = 'open';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_COMPLETED = 'completed';
    public const STATE_CANCELLED = 'cancelled';
}
