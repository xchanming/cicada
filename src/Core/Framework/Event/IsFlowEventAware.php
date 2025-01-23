<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;

#[Package('fundamentals@after-sales')]
#[\Attribute(\Attribute::TARGET_CLASS)]
class IsFlowEventAware
{
}
