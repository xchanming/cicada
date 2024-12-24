<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Newsletter\Subscribe;

use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\PageLoadedEvent;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class NewsletterSubscribePageLoadedEvent extends PageLoadedEvent
{
    /**
     * @var NewsletterSubscribePage
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $page;

    public function __construct(
        NewsletterSubscribePage $page,
        SalesChannelContext $salesChannelContext,
        Request $request
    ) {
        $this->page = $page;
        parent::__construct($salesChannelContext, $request);
    }

    public function getPage(): NewsletterSubscribePage
    {
        return $this->page;
    }
}
