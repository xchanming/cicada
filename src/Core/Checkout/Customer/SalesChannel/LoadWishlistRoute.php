<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerWishlist\CustomerWishlistEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\CustomerWishlistLoaderCriteriaEvent;
use Cicada\Core\Checkout\Customer\Event\CustomerWishlistProductListingResultEvent;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\AbstractProductCloseoutFilterFactory;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class LoadWishlistRoute extends AbstractLoadWishlistRoute
{
    /**
     * @internal
     *
     * @param EntityRepository<CustomerWishlistCollection> $wishlistRepository
     * @param SalesChannelRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly EntityRepository $wishlistRepository,
        private readonly SalesChannelRepository $productRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SystemConfigService $systemConfigService,
        private readonly AbstractProductCloseoutFilterFactory $productCloseoutFilterFactory
    ) {
    }

    public function getDecorated(): AbstractLoadWishlistRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/customer/wishlist', name: 'store-api.customer.wishlist.load', methods: ['GET', 'POST'], defaults: ['_loginRequired' => true, '_entity' => 'product'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria, CustomerEntity $customer): LoadWishlistRouteResponse
    {
        if ($criteria->getTitle() === null) {
            $criteria->setTitle('wishlist::load-products');
        }

        if (!$this->systemConfigService->get('core.cart.wishlistEnabled', $context->getSalesChannelId())) {
            throw CustomerException::customerWishlistNotActivated();
        }

        $wishlist = $this->loadWishlist($context, $customer->getId());
        $products = $this->loadProducts($wishlist->getId(), $criteria, $context, $request);

        return new LoadWishlistRouteResponse($wishlist, $products);
    }

    private function loadWishlist(SalesChannelContext $context, string $customerId): CustomerWishlistEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            new EqualsFilter('salesChannelId', $context->getSalesChannelId()),
        ]));

        $wishlist = $this->wishlistRepository->search($criteria, $context->getContext());
        $result = $wishlist->first();
        if (!$result instanceof CustomerWishlistEntity) {
            throw CustomerException::customerWishlistNotFound();
        }

        return $result;
    }

    /**
     * @return EntitySearchResult<ProductCollection>
     */
    private function loadProducts(string $wishlistId, Criteria $criteria, SalesChannelContext $context, Request $request): EntitySearchResult
    {
        $criteria->addFilter(
            new EqualsFilter('wishlists.wishlistId', $wishlistId)
        );

        $criteria->addSorting(
            new FieldSorting('wishlists.updatedAt', FieldSorting::DESCENDING)
        );

        $criteria->addSorting(
            new FieldSorting('wishlists.createdAt', FieldSorting::DESCENDING)
        );

        if ($this->systemConfigService->getBool(
            'core.listing.hideCloseoutProductsWhenOutOfStock',
            $context->getSalesChannelId()
        )) {
            $criteria->addFilter(
                $this->productCloseoutFilterFactory->create($context)
            );
        }

        $event = new CustomerWishlistLoaderCriteriaEvent($criteria, $context);
        $this->eventDispatcher->dispatch($event);

        $products = $this->productRepository->search($criteria, $context);

        $event = new CustomerWishlistProductListingResultEvent($request, $products, $context);
        $this->eventDispatcher->dispatch($event);

        return $products;
    }
}
