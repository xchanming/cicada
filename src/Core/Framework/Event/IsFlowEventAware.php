<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;

#[Package('services-settings')]
#[\Attribute(\Attribute::TARGET_CLASS)]
class IsFlowEventAware
{
}
