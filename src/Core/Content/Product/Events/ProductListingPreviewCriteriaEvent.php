<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
class ProductListingPreviewCriteriaEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @var Criteria
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $criteria;

    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $context;

    public function __construct(
        Criteria $criteria,
        SalesChannelContext $context
    ) {
        $this->criteria = $criteria;
        $this->context = $context;
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
