<?php declare(strict_types=1);

namespace Cicada\Core\System\Currency\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@framework')]
class CurrencyRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
}
