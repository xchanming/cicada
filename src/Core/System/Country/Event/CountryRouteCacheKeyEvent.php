<?php declare(strict_types=1);

namespace Cicada\Core\System\Country\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@discovery')]
class CountryRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}
