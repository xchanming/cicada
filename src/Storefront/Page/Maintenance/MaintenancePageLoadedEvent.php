<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Maintenance;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
class MaintenancePageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var MaintenancePage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        MaintenancePage $page,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): MaintenancePage
    {
        return $this->page;
    }
}
