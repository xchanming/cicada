<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Shipping\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class ShippingMethodRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
}
