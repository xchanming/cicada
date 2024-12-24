<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Search;

use Cicada\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Cicada\Core\Content\Product\Events\ProductSearchResultEvent;
use Cicada\Core\Content\Product\ProductEvents;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\CompositeListingProcessor;
use Cicada\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('services-settings')]
class ResolvedCriteriaProductSearchRoute extends AbstractProductSearchRoute
{
    final public const DEFAULT_SEARCH_SORT = 'score';
    final public const STATE = 'search-route-context';

    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractProductSearchRoute $decorated,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DefinitionInstanceRegistry $registry,
        private readonly RequestCriteriaBuilder $criteriaBuilder,
        private readonly CompositeListingProcessor $processor
    ) {
    }

    public function getDecorated(): AbstractProductSearchRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/search', name: 'store-api.search', methods: ['POST'], defaults: ['_entity' => 'product'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSearchRouteResponse
    {
        if (!$request->get('order')) {
            $request->request->set('order', self::DEFAULT_SEARCH_SORT);
        }

        $criteria->addState(self::STATE);

        $criteria = $this->criteriaBuilder->handleRequest(
            $request,
            $criteria,
            $this->registry->getByEntityName('product'),
            $context->getContext()
        );

        // will be handled via processor in next line
        $criteria->setLimit(null);

        $this->processor->prepare($request, $criteria, $context);

        $this->eventDispatcher->dispatch(
            new ProductSearchCriteriaEvent($request, $criteria, $context),
            ProductEvents::PRODUCT_SEARCH_CRITERIA
        );

        $response = $this->getDecorated()->load($request, $context, $criteria);

        $this->processor->process($request, $response->getListingResult(), $context);

        $this->eventDispatcher->dispatch(
            new ProductSearchResultEvent($request, $response->getListingResult(), $context),
            ProductEvents::PRODUCT_SEARCH_RESULT
        );

        $response->getListingResult()->addCurrentFilter('search', $request->get('search'));

        return $response;
    }
}
