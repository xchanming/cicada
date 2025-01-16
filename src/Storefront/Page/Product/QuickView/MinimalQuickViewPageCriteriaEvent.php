<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\QuickView;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

#[Package('framework')]
class MinimalQuickViewPageCriteriaEvent extends Event implements CicadaSalesChannelEvent
{
    /**
     * @var string
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $productId;

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
        string $productId,
        Criteria $criteria,
        SalesChannelContext $context
    ) {
        $this->productId = $productId;
        $this->criteria = $criteria;
        $this->context = $context;
    }

    public function getProductId(): string
    {
        return $this->productId;
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
