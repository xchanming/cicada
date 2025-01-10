<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\WishlistProductAddedEvent;
use Cicada\Core\Content\Product\Exception\ProductNotFoundException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class AddWishlistProductRoute extends AbstractAddWishlistProductRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $wishlistRepository,
        private readonly SalesChannelRepository $productRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDecorated(): AbstractAddWishlistProductRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/customer/wishlist/add/{productId}', name: 'store-api.customer.wishlist.add', methods: ['POST'], defaults: ['_loginRequired' => true])]
    public function add(string $productId, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        if (!$this->systemConfigService->get('core.cart.wishlistEnabled', $context->getSalesChannelId())) {
            throw CustomerException::customerWishlistNotActivated();
        }

        $this->validateProduct($productId, $context);
        $wishlistId = $this->getWishlistId($context, $customer->getId());

        $this->wishlistRepository->upsert([
            [
                'id' => $wishlistId,
                'customerId' => $customer->getId(),
                'salesChannelId' => $context->getSalesChannelId(),
                'products' => [
                    [
                        'productId' => $productId,
                        'productVersionId' => Defaults::LIVE_VERSION,
                    ],
                ],
            ],
        ], $context->getContext());

        $this->eventDispatcher->dispatch(new WishlistProductAddedEvent($wishlistId, $productId, $context));

        return new SuccessResponse();
    }

    private function getWishlistId(SalesChannelContext $context, string $customerId): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            new EqualsFilter('salesChannelId', $context->getSalesChannelId()),
        ]));

        $wishlistIds = $this->wishlistRepository->searchIds($criteria, $context->getContext());

        if ($wishlistIds->firstId() === null) {
            return Uuid::randomHex();
        }

        return $wishlistIds->firstId();
    }

    private function validateProduct(string $productId, SalesChannelContext $context): void
    {
        $productsIds = $this->productRepository->searchIds(new Criteria([$productId]), $context);

        if ($productsIds->firstId() === null) {
            throw new ProductNotFoundException($productId);
        }
    }
}
