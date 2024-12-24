<?php declare(strict_types=1);

namespace Cicada\Core\System\SalesChannel\Event;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class SalesChannelContextRestorerOrderCriteriaEvent extends NestedEvent
{
    public function __construct(
        protected Criteria $criteria,
        protected Context $context
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }
}
