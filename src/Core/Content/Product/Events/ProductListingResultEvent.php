<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\Events;

use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('inventory')]
class ProductListingResultEvent extends NestedEvent implements CicadaSalesChannelEvent
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
     * @var ProductListingResult
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $result;

    public function __construct(
        Request $request,
        ProductListingResult $result,
        SalesChannelContext $context
    ) {
        $this->request = $request;
        $this->context = $context;
        $this->result = $result;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->context;
    }

    public function getResult(): ProductListingResult
    {
        return $this->result;
    }
}
