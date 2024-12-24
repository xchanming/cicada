<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\Event;

use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Allows the manipulation of the sales channel context after it was assembled from the order
 */
#[Package('checkout')]
class SalesChannelContextAssembledEvent extends Event implements CicadaSalesChannelEvent
{
    /**
     * @internal
     */
    public function __construct(
        private readonly OrderEntity $order,
        private readonly SalesChannelContext $salesChannelContext,
    ) {
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
