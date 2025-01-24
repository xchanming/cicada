<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('framework')]
interface CicadaSalesChannelEvent extends CicadaEvent
{
    public function getSalesChannelContext(): SalesChannelContext;
}
