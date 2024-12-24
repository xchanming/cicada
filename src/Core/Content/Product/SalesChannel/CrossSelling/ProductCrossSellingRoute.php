<?php declare(strict_types=1);

namespace Cicada\Core\Content\Product\SalesChannel\CrossSelling;

use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingCollection;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingEntity;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\Events\ProductCrossSellingCriteriaLoadEvent;
use Cicada\Core\Content\Product\Events\ProductCrossSellingIdsCriteriaEvent;
use Cicada\Core\Content\Product\Events\ProductCrossSellingsLoadedEvent;
use Cicada\Core\Content\Product\Events\ProductCrossSellingStreamCriteriaEvent;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\AbstractProductCloseoutFilterFactory;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingLoader;
use Cicada\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Cicada\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Cicada\Core\Framework\Adapter\Cache\Event\AddCacheTagEvent;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('inventory')]
class ProductCrossSellingRoute extends AbstractProductCrossSellingRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $crossSellingRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProductStreamBuilderInterface $productStreamBuilder,
        private readonly SalesChannelRepository $productRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly ProductListingLoader $listingLoader,
        private readonly AbstractProductCloseoutFilterFactory $productCloseoutFilterFactory,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public function getDecorated(): AbstractProductCrossSellingRoute
    {
        throw new DecorationPatternException(self::class);
    }

    public static function buildName(string $id): string
    {
        return EntityCacheKeyGenerator::buildProductTag($id);
    }

    #[Route(path: '/store-api/product/{productId}/cross-selling', name: 'store-api.product.cross-selling', methods: ['POST'], defaults: ['_entity' => 'product'])]
    public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductCrossSellingRouteResponse
    {
        $this->dispatcher->dispatch(new AddCacheTagEvent(self::buildName($productId)));

        $crossSellings = $this->loadCrossSellings($productId, $context);

        $elements = new CrossSellingElementCollection();

        foreach ($crossSellings as $crossSelling) {
            $clone = clone $criteria;
            if ($this->useProductStream($crossSelling)) {
                $element = $this->loadByStream($crossSelling, $context, $clone);
            } else {
                $element = $this->loadByIds($crossSelling, $context, $clone);
            }

            $elements->add($element);
        }

        $this->eventDispatcher->dispatch(new ProductCrossSellingsLoadedEvent($elements, $context));

        return new ProductCrossSellingRouteResponse($elements);
    }

    private function loadCrossSellings(string $productId, SalesChannelContext $context): ProductCrossSellingCollection
    {
        $criteria = new Criteria();
        $criteria
            ->setTitle('product-cross-selling-route')
            ->addAssociation('assignedProducts')
            ->addFilter(new EqualsFilter('product.id', $productId))
            ->addFilter(new EqualsFilter('active', 1))
            ->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $this->eventDispatcher->dispatch(
            new ProductCrossSellingCriteriaLoadEvent($criteria, $context)
        );

        /** @var ProductCrossSellingCollection $crossSellings */
        $crossSellings = $this->crossSellingRepository
            ->search($criteria, $context->getContext())
            ->getEntities();

        return $crossSellings;
    }

    private function loadByStream(ProductCrossSellingEntity $crossSelling, SalesChannelContext $context, Criteria $criteria): CrossSellingElement
    {
        /** @var string $productStreamId */
        $productStreamId = $crossSelling->getProductStreamId();

        $filters = $this->productStreamBuilder->buildFilters(
            $productStreamId,
            $context->getContext()
        );

        $criteria->addFilter(...$filters)
            ->addFilter(new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('product.id', $crossSelling->getProductId())]))
            ->setOffset(0)
            ->setLimit($crossSelling->getLimit())
            ->addSorting($crossSelling->getSorting());

        $criteria = $this->handleAvailableStock($criteria, $context);

        $this->eventDispatcher->dispatch(
            new ProductCrossSellingStreamCriteriaEvent($crossSelling, $criteria, $context)
        );

        $searchResult = $this->listingLoader->load($criteria, $context);

        /** @var ProductCollection $products */
        $products = $searchResult->getEntities();

        $element = new CrossSellingElement();
        $element->setCrossSelling($crossSelling);
        $element->setProducts($products);
        $element->setStreamId($crossSelling->getProductStreamId());

        $element->setTotal($products->count());

        return $element;
    }

    private function loadByIds(ProductCrossSellingEntity $crossSelling, SalesChannelContext $context, Criteria $criteria): CrossSellingElement
    {
        $element = new CrossSellingElement();
        $element->setCrossSelling($crossSelling);
        $element->setProducts(new ProductCollection());
        $element->setTotal(0);

        if (!$crossSelling->getAssignedProducts()) {
            return $element;
        }

        $crossSelling->getAssignedProducts()->sortByPosition();

        $ids = array_values($crossSelling->getAssignedProducts()->getProductIds());

        $filter = new ProductAvailableFilter(
            $context->getSalesChannel()->getId(),
            ProductVisibilityDefinition::VISIBILITY_LINK
        );

        if (!\count($ids)) {
            return $element;
        }

        $criteria->setIds($ids);
        $criteria->addFilter($filter);
        $criteria->addAssociation('options.group');

        $criteria = $this->handleAvailableStock($criteria, $context);

        $this->eventDispatcher->dispatch(
            new ProductCrossSellingIdsCriteriaEvent($crossSelling, $criteria, $context)
        );

        $result = $this->productRepository
            ->search($criteria, $context);

        /** @var ProductCollection $products */
        $products = $result->getEntities();

        $ids = $criteria->getIds();
        $products->sortByIdArray($ids);

        $element->setProducts($products);
        $element->setTotal(\count($products));

        return $element;
    }

    private function handleAvailableStock(Criteria $criteria, SalesChannelContext $context): Criteria
    {
        $salesChannelId = $context->getSalesChannel()->getId();
        $hide = $this->systemConfigService->get('core.listing.hideCloseoutProductsWhenOutOfStock', $salesChannelId);

        if (!$hide) {
            return $criteria;
        }

        $closeoutFilter = $this->productCloseoutFilterFactory->create($context);
        $criteria->addFilter($closeoutFilter);

        return $criteria;
    }

    private function useProductStream(ProductCrossSellingEntity $crossSelling): bool
    {
        return $crossSelling->getType() === ProductCrossSellingDefinition::TYPE_PRODUCT_STREAM
            && $crossSelling->getProductStreamId() !== null;
    }
}
