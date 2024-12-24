<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart;

use Cicada\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Struct\Struct;

#[Package('checkout')]
class PaymentTransactionStruct extends Struct
{
    public function __construct(
        protected string $orderTransactionId,
        protected ?string $returnUrl = null,
        protected ?RecurringDataStruct $recurring = null
    ) {
    }

    public function getOrderTransactionId(): string
    {
        return $this->orderTransactionId;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function getRecurring(): ?RecurringDataStruct
    {
        return $this->recurring;
    }

    public function isRecurring(): bool
    {
        return $this->recurring !== null;
    }
}
