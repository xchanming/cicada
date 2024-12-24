<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('inventory')]
class ProductSuggestRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
}
