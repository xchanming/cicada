<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Navigation\Error;

use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Content\Cms\CmsPageCollection;
use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class ErrorPageLoader implements ErrorPageLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly SalesChannelCmsPageLoaderInterface $cmsPageLoader,
        private readonly GenericPageLoaderInterface $genericLoader,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     * @throws PageNotFoundException
     */
    public function load(string $cmsErrorLayoutId, Request $request, SalesChannelContext $context): ErrorPage
    {
        $page = $this->genericLoader->load($request, $context);
        $page = ErrorPage::createFrom($page);

        /** @var CmsPageCollection $pages */
        $pages = $this->cmsPageLoader->load($request, new Criteria([$cmsErrorLayoutId]), $context)->getEntities();

        if (!$pages->has($cmsErrorLayoutId)) {
            throw new PageNotFoundException($cmsErrorLayoutId);
        }

        $page->setCmsPage($pages->get($cmsErrorLayoutId));

        $this->eventDispatcher->dispatch(new ErrorPageLoadedEvent($page, $context, $request));

        return $page;
    }
}
