<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Content\Product\SalesChannel\Listing\FilterCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class ProductListingCollectFilterEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @var Request
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $request;

    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $context;

    /**
     * @var FilterCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $filters;

    public function __construct(
        Request $request,
        FilterCollection $filters,
        SalesChannelContext $context
    ) {
        $this->request = $request;
        $this->context = $context;
        $this->filters = $filters;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getFilters(): FilterCollection
    {
        return $this->filters;
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
