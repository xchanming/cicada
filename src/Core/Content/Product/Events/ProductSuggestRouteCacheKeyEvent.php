<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductSuggestRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}
