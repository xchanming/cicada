<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Checkout\Finish;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('framework')]
class CheckoutFinishPageOrderCriteriaEvent implements CicadaSalesChannelEvent
{
    public function __construct(
        protected Criteria $criteria,
        protected SalesChannelContext $context
    ) {
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }
}
