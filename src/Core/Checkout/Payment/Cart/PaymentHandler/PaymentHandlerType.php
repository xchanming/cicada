<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Cart\PaymentHandler;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
enum PaymentHandlerType
{
    case RECURRING;
    case REFUND;
}
