<?php declare(strict_types=1);

namespace Cicada\Core\System\Salutation\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheKeyEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class SalutationRouteCacheKeyEvent extends StoreApiRouteCacheKeyEvent
{
}
