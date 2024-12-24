<?php declare(strict_types=1);

namespace Cicada\Core\Content\Category\Event;

use Cicada\Core\Content\Category\Tree\Tree;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Event\CicadaSalesChannelEvent;
use Cicada\Core\Framework\Event\NestedEvent;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('inventory')]
class NavigationLoadedEvent extends NestedEvent implements CicadaSalesChannelEvent
{
    /**
     * @var Tree
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $navigation;

    /**
     * @var SalesChannelContext
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $salesChannelContext;

    public function __construct(
        Tree $navigation,
        SalesChannelContext $salesChannelContext
    ) {
        $this->navigation = $navigation;
        $this->salesChannelContext = $salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getNavigation(): Tree
    {
        return $this->navigation;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }
}
