<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\Search;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingLoader;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Cicada\Core\Content\Product\SearchKeyword\ProductSearchBuilderInterface;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('services-settings')]
class ProductSearchRoute extends AbstractProductSearchRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ProductSearchBuilderInterface $searchBuilder,
        private readonly ProductListingLoader $productListingLoader
    ) {
    }

    public function getDecorated(): AbstractProductSearchRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/search', name: 'store-api.search', methods: ['POST'], defaults: ['_entity' => 'product'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSearchRouteResponse
    {
        if (!$request->get('search')) {
            throw RoutingException::missingRequestParameter('search');
        }

        $criteria->addState(Criteria::STATE_ELASTICSEARCH_AWARE);

        $criteria->addFilter(
            new ProductAvailableFilter($context->getSalesChannelId(), ProductVisibilityDefinition::VISIBILITY_SEARCH)
        );

        $this->searchBuilder->build($request, $criteria, $context);

        $result = $this->productListingLoader->load($criteria, $context);

        $result = ProductListingResult::createFrom($result);

        return new ProductSearchRouteResponse($result);
    }
}
