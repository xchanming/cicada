<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Maintenance;

use Cicada\Core\Content\Cms\Exception\PageNotFoundException;
use Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class MaintenancePageLoader
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
     * @throws PageNotFoundException
     */
    public function load(string $cmsErrorLayoutId, Request $request, SalesChannelContext $context): MaintenancePage
    {
        try {
            $page = $this->genericLoader->load($request, $context);
            $page = MaintenancePage::createFrom($page);

            $pages = $this->cmsPageLoader->load($request, new Criteria([$cmsErrorLayoutId]), $context)->getEntities();

            $cmsPage = $pages->first();
            if ($cmsPage === null) {
                throw new PageNotFoundException($cmsErrorLayoutId);
            }

            $page->setCmsPage($cmsPage);

            $this->eventDispatcher->dispatch(new MaintenancePageLoadedEvent($page, $context, $request));

            return $page;
        } catch (\Exception) {
            throw new PageNotFoundException($cmsErrorLayoutId);
        }
    }
}
