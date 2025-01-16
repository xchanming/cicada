<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\QuickView;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('framework')]
class MinimalQuickViewPageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var MinimalQuickViewPage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        MinimalQuickViewPage $page,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): MinimalQuickViewPage
    {
        return $this->page;
    }
}
