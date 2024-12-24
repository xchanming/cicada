<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('core')]
class SalesChannelContextRestoredEvent extends NestedEvent
{
    public function __construct(
        private readonly SalesChannelContext $restoredContext,
        private readonly SalesChannelContext $currentContext
    ) {
    }

    public function getRestoredSalesChannelContext(): SalesChannelContext
    {
        return $this->restoredContext;
    }

    public function getContext(): Context
    {
        return $this->restoredContext->getContext();
    }

    public function getCurrentSalesChannelContext(): SalesChannelContext
    {
        return $this->currentContext;
    }
}
