<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
class SalesChannelContextSwitchEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    public function __construct(
        private readonly SalesChannelContext $salesChannelContext,
        private readonly DataBag $requestDataBag
    ) {
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getRequestDataBag(): DataBag
    {
        return $this->requestDataBag;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
