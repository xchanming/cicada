<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Newsletter\Account;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Pagelet\PageletLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class NewsletterAccountPageletLoadedEvent extends PageletLoadedEvent
{
    public function __construct(
        protected NewsletterAccountPagelet $pagelet,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        parent::__construct($salesChannelContext, $request);
    }

    public function getPagelet(): NewsletterAccountPagelet
    {
        return $this->pagelet;
    }
}
