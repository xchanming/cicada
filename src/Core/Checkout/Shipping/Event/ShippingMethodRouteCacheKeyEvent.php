<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Shipping\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class ShippingMethodRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}
