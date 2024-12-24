<?php declare(strict_types=1);

namespace Cicada\Storefront\Event\RouteRequest;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
abstract class RouteRequestEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    private readonly Criteria $criteria;

    public function __construct(
        private readonly Request $storefrontRequest,
        private readonly Request $storeApiRequest,
        private readonly SalesChannelContext $salesChannelContext,
        ?Criteria $criteria = null
    ) {
        $this->criteria = $criteria ?? new Criteria();
    }

    public function getStorefrontRequest(): Request
    {
        return $this->storefrontRequest;
    }

    public function getStoreApiRequest(): Request
    {
        return $this->storeApiRequest;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }
}
