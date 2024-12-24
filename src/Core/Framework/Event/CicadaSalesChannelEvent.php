<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Event;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
interface CicadaSalesChannelEvent extends CicadaEvent
{
    public function getSalesChannelContext(): SalesChannelContext;
}
