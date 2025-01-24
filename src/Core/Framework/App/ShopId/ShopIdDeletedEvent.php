<?php declare(strict_types=1);

namespace Cicada\Core\Framework\App\ShopId;

use Cicada\Core\Framework\Log\Package;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[Package('framework')]
class ShopIdDeletedEvent extends Event
{
}
