<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Footer;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Pagelet\PageletLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
class FooterPageletLoadedEvent extends PageletLoadedEvent
{
    /**
     * @var FooterPagelet
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $pagelet;

    public function __construct(
        FooterPagelet $pagelet,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->pagelet = $pagelet;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPagelet(): FooterPagelet
    {
        return $this->pagelet;
    }
}
