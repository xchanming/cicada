<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Suggest;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Cicada\Core\Content\Product\Events\ProductSuggestResultEvent;
use Cicada\Core\Content\Product\ProductEvents;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\CompositeListingProcessor;
use Cicada\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Cicada\Core\Content\Product\SearchKeyword\ProductSearchBuilderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('services-settings')]
class ResolvedCriteriaProductSuggestRoute extends AbstractProductSuggestRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductSearchBuilderInterface $searchBuilder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AbstractProductSuggestRoute $decorated,
        private readonly CompositeListingProcessor $processor
    ) {
    }

    public function getDecorated(): AbstractProductSuggestRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/search-suggest', name: 'store-api.search.suggest', methods: ['POST'], defaults: ['_entity' => 'product'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSuggestRouteResponse
    {
        if (!$request->get('search')) {
            throw RoutingException::missingRequestParameter('search');
        }

        $criteria->addState(ProductSuggestRoute::STATE);
        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $criteria->addFilter(
            new ProductAvailableFilter($context->getSalesChannelId(), ProductVisibilityDefinition::VISIBILITY_SEARCH)
        );

        $this->searchBuilder->build($request, $criteria, $context);

        $this->processor->prepare($request, $criteria, $context);

        $this->eventDispatcher->dispatch(
            new ProductSuggestCriteriaEvent($request, $criteria, $context),
            ProductEvents::PRODUCT_SUGGEST_CRITERIA
        );

        $response = $this->getDecorated()->load($request, $context, $criteria);

        $this->processor->process($request, $response->getListingResult(), $context);

        $this->eventDispatcher->dispatch(
            new ProductSuggestResultEvent($request, $response->getListingResult(), $context),
            ProductEvents::PRODUCT_SUGGEST_RESULT
        );

        return $response;
    }
}
