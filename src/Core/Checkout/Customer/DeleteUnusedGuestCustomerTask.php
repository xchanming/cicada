<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

#[Package('checkout')]
class DeleteUnusedGuestCustomerTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'customer.delete_unused_guests';
    }

    public static function getDefaultInterval(): int
    {
        return self::DAILY;
    }
}
