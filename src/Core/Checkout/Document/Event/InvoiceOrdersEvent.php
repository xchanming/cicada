<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Document\Event;

use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
final class InvoiceOrdersEvent extends DocumentOrderEvent
{
}
