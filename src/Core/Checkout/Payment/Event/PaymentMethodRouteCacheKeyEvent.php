<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Payment\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class PaymentMethodRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}
