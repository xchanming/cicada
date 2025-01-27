<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Log\Package;

#[Package('framework')]
interface CicadaEvent
{
    public function getContext(): Context;
}
