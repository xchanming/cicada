<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Review\Event;

use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewResult;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('after-sales')]
final class ProductReviewsLoadedEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    public function __construct(
        public ProductReviewResult $reviews,
        public Request $request,
        protected SalesChannelContext $salesChannelContext,
    ) {
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
