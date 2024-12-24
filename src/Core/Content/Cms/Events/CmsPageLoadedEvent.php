<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\Events;

use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

#[Package('buyers-experience')]
class CmsPageLoadedEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @var Request
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $request;

    /**
     * @var CmsPageCollection
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $result;

    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelContext;

    /**
     * @param CmsPageCollection $result
     */
    public function __construct(
        Request $request,
        EntityCollection $result,
        SalesChannelContext $salesChannelContext
    ) {
        $this->request = $request;
        $this->result = $result;
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return CmsPageCollection
     */
    public function getResult(): EntityCollection
    {
        return $this->result;
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
