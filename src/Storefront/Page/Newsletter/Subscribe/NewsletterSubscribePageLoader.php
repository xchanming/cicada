<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Newsletter\Subscribe;

use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('checkout')]
class NewsletterSubscribePageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): NewsletterSubscribePage
    {
        $page = $this->genericLoader->load($request, $salesChannelContext);
        $page = NewsletterSubscribePage::createFrom($page);

        $this->eventDispatcher->dispatch(
            new NewsletterSubscribePageLoadedEvent($page, $salesChannelContext, $request)
        );

        return $page;
    }
}
