<?php declare(strict_types=1);

namespace Cicada\Core\System\Salutation\Event;

use Cicada\Core\Framework\Adapter\Cache\StoreApiRouteCacheTagsEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('checkout')]
class SalutationRouteCacheTagsEvent extends StoreApiRouteCacheTagsEvent
{
}
