<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
abstract class PageletLoadedEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelContext;

    /**
     * @var Request
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $request;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->request = $request;
    }

    /**
     * @return Pagelet
     */
    abstract public function getPagelet();

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
