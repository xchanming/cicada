<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\CustomerException;
use Cicada\Core\Checkout\Customer\Event\WishlistMergedEvent;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\DataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SuccessResponse;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('checkout')]
class MergeWishlistProductRoute extends AbstractMergeWishlistProductRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $wishlistRepository,
        private readonly SalesChannelRepository $productRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Connection $connection
    ) {
    }

    public function getDecorated(): AbstractMergeWishlistProductRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/customer/wishlist/merge', name: 'store-api.customer.wishlist.merge', methods: ['POST'], defaults: ['_loginRequired' => true])]
    public function merge(RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): SuccessResponse
    {
        if (!$this->systemConfigService->get('core.cart.wishlistEnabled', $context->getSalesChannel()->getId())) {
            throw CustomerException::customerWishlistNotActivated();
        }

        $wishlistId = $this->getWishlistId($context, $customer->getId());

        $upsertData = $this->buildUpsertProducts($data, $wishlistId, $context);

        $this->wishlistRepository->upsert([[
            'id' => $wishlistId,
            'customerId' => $customer->getId(),
            'salesChannelId' => $context->getSalesChannel()->getId(),
            'products' => $upsertData,
        ]], $context->getContext());

        $this->eventDispatcher->dispatch(new WishlistMergedEvent($upsertData, $context));

        return new SuccessResponse();
    }

    private function getWishlistId(SalesChannelContext $context, string $customerId): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('customerId', $customerId),
            new EqualsFilter('salesChannelId', $context->getSalesChannel()->getId()),
        ]));

        $wishlistIds = $this->wishlistRepository->searchIds($criteria, $context->getContext());

        return $wishlistIds->firstId() ?? Uuid::randomHex();
    }

    /**
     * @return array<array{id: string, productId?: string, productVersionId?: Defaults::LIVE_VERSION}>
     */
    private function buildUpsertProducts(RequestDataBag $data, string $wishlistId, SalesChannelContext $context): array
    {
        $productIds = $data->get('productIds');
        if (!$productIds instanceof DataBag) {
            throw CustomerException::productIdsParameterIsMissing();
        }

        $ids = array_unique(array_filter($productIds->all()));

        if (\count($ids) === 0) {
            return [];
        }

        /** @var array<string> $ids */
        $ids = $this->productRepository->searchIds(new Criteria($ids), $context)->getIds();

        $customerProducts = $this->loadCustomerProducts($wishlistId, $ids);

        $upsertData = [];

        /** @var string $id * */
        foreach ($ids as $id) {
            if (\array_key_exists($id, $customerProducts)) {
                $upsertData[] = [
                    'id' => $customerProducts[$id],
                ];

                continue;
            }

            $upsertData[] = [
                'id' => Uuid::randomHex(),
                'productId' => $id,
                'productVersionId' => Defaults::LIVE_VERSION,
            ];
        }

        return $upsertData;
    }

    /**
     * @param array<string> $productIds
     *
     * @return array<string, string>
     */
    private function loadCustomerProducts(string $wishlistId, array $productIds): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'LOWER(HEX(`product_id`)) as `product_id`',
            'LOWER(HEX(`id`)) as id',
        );
        $query->from('`customer_wishlist_product`');
        $query->where('`customer_wishlist_id` = :id');
        $query->andWhere('`product_id` IN (:productIds)');
        $query->setParameter('id', Uuid::fromHexToBytes($wishlistId));
        $query->setParameter('productIds', Uuid::fromHexToBytesList($productIds), ArrayParameterType::BINARY);
        $result = $query->executeQuery();

        /** @var array<string, string> $values */
        $values = $result->fetchAllKeyValue();

        return $values;
    }
}
