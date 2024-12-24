<?php declare(strict_types=1);

namespace Cicada\Storefront\Pagelet\Menu\Offcanvas;

use Cicada\Core\Content\Category\Exception\CategoryNotFoundException;
use Cicada\Core\Content\Category\Service\NavigationLoaderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageletLoader. Always use a store-api route to get or put data.
 */
#[Package('storefront')]
class MenuOffcanvasPageletLoader implements MenuOffcanvasPageletLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly NavigationLoaderInterface $navigationLoader
    ) {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $context): MenuOffcanvasPagelet
    {
        $navigationId = (string) $request->query->get('navigationId', $context->getSalesChannel()->getNavigationCategoryId());
        if (!$navigationId) {
            throw RoutingException::missingRequestParameter('navigationId');
        }

        $navigation = $this->navigationLoader->load($navigationId, $context, $navigationId, 1);

        $pagelet = new MenuOffcanvasPagelet($navigation);

        $this->eventDispatcher->dispatch(
            new MenuOffcanvasPageletLoadedEvent($pagelet, $context, $request)
        );

        return $pagelet;
    }
}
