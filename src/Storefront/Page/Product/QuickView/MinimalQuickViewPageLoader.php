<?php declare(strict_types=1);

namespace Cicada\Storefront\Page\Product\QuickView;

use Cicada\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Do not use direct or indirect repository calls in a PageLoader. Always use a store-api route to get or put data.
 */
#[Package('framework')]
class MinimalQuickViewPageLoader
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractProductDetailRoute $productRoute
    ) {
    }

    /**
     * @throws RoutingException
     */
    public function load(Request $request, SalesChannelContext $salesChannelContext): MinimalQuickViewPage
    {
        $productId = $request->get('productId');
        if (!$productId) {
            throw RoutingException::missingRequestParameter('productId', '/productId');
        }

        $criteria = (new Criteria())
            ->addAssociation('manufacturer.media')
            ->addAssociation('options.group')
            ->addAssociation('properties.group')
            ->addAssociation('mainCategories.category');

        $criteria
            ->getAssociation('media')
            ->addSorting(new FieldSorting('position'));

        $this->eventDispatcher->dispatch(new MinimalQuickViewPageCriteriaEvent($productId, $criteria, $salesChannelContext));

        $result = $this->productRoute->load($productId, $request->duplicate(), $salesChannelContext, $criteria);
        $product = $result->getProduct();

        $page = new MinimalQuickViewPage($product);

        $event = new MinimalQuickViewPageLoadedEvent($page, $salesChannelContext, $request);

        $this->eventDispatcher->dispatch($event);

        return $page;
    }
}
