<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\DataAbstractionLayer\Indexing;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductIndexer;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('slow')]
class ProductRatingAverageIndexerTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepository<ProductReviewCollection>
     */
    private EntityRepository $reviewRepository;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $productRepository;

    private SalesChannelContext $salesChannel;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerRepository;

    private Connection $connection;

    private ProductIndexer $productIndexer;

    protected function setUp(): void
    {
        $this->reviewRepository = static::getContainer()->get('product_review.repository');
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->customerRepository = static::getContainer()->get('customer.repository');
        $this->salesChannel = static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $this->connection = static::getContainer()->get(Connection::class);
        $this->productIndexer = static::getContainer()->get(ProductIndexer::class);
    }

    /**
     * tests that a update of promotion exclusions is written in excluded promotions too
     */
    #[Group('reviews')]
    public function testUpsertReviewIndexerLogic(): void
    {
        $productId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();

        $this->createProduct($productId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 1.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productId, true);

        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals($pointsOnAReview, $product->getRatingAverage());

        $expected = ($pointsOnAReview + $pointsOnBReview) / 2;
        $this->createReview($reviewBId, $pointsOnBReview, $productId, true);
        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals($expected, $product->getRatingAverage());
    }

    /**
     * tests that a deactivated review is not considered for calculation
     * rating would be 3, but because the reviewA is deactivated only reviewB points will
     * be taken for calculation
     */
    #[Group('reviews')]
    public function testThatDeactivatedReviewsAreNotCalculated(): void
    {
        $productId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();

        $this->createProduct($productId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 1.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productId, false);
        $this->createReview($reviewBId, $pointsOnBReview, $productId, true);

        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals($pointsOnBReview, $product->getRatingAverage());
    }

    /**
     * tests that a deactivating/activating reviews are considered correctly
     */
    #[Group('reviews')]
    public function testThatUpdatingReviewsTriggerCalculationProcessCorrectly(): void
    {
        $productId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();

        $this->createProduct($productId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 1.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productId, false);
        $this->createReview($reviewBId, $pointsOnBReview, $productId, true);

        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals($pointsOnBReview, $product->getRatingAverage());

        $this->updateReview([['id' => $reviewAId, 'status' => true]]);

        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());

        $expected = ($pointsOnAReview + $pointsOnBReview) / 2;

        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals($expected, $product->getRatingAverage());
    }

    /**
     * tests that a multi save reviews are considered correctly
     */
    #[Group('reviews')]
    public function testMultiReviewsSaveProcess(): void
    {
        $productAId = Uuid::randomHex();
        $productBId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();
        $reviewCId = Uuid::randomHex();

        $this->createProduct($productAId);
        $this->createProduct($productBId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 5.0;
        $pointsOnCReview = 2.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productAId, false);
        $this->createReview($reviewBId, $pointsOnBReview, $productAId, false);
        $this->createReview($reviewCId, $pointsOnCReview, $productAId, true);

        $products = $this->productRepository->search(new Criteria([$productAId, $productBId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertInstanceOf(ProductEntity::class, $productB = $products->get($productBId));

        static::assertEquals(2.0, $productA->getRatingAverage());
        static::assertEquals(0.0, $productB->getRatingAverage());

        $this->updateReview([['id' => $reviewAId, 'status' => true], ['id' => $reviewBId, 'status' => true], ['id' => $reviewCId, 'productId' => $productBId, 'status' => true]]);
        $products = $this->productRepository->search(new Criteria([$productAId, $productBId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertInstanceOf(ProductEntity::class, $productB = $products->get($productBId));
        static::assertEquals(5.0, $productA->getRatingAverage());
        static::assertEquals(2.0, $productB->getRatingAverage());
    }

    /**
     * tests that deactivating product reviews result in correct review score, even if no review is active (=>0)
     */
    #[Group('reviews')]
    public function testCalculateWhenSwitchingReviewStatus(): void
    {
        $productAId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();

        $this->createProduct($productAId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 2.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productAId, true);
        $this->createReview($reviewBId, $pointsOnBReview, $productAId, true);

        $products = $this->productRepository->search(new Criteria([$productAId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertEquals(3.5, $productA->getRatingAverage());

        $this->updateReview([['id' => $reviewAId, 'status' => false]]);
        $products = $this->productRepository->search(new Criteria([$productAId]), $this->salesChannel->getContext());
        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertEquals(2.0, $productA->getRatingAverage());

        $this->updateReview([['id' => $reviewBId, 'status' => false]]);
        $products = $this->productRepository->search(new Criteria([$productAId]), $this->salesChannel->getContext());
        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertEquals(0.0, $productA->getRatingAverage());
    }

    /**
     * tests that deactivating product reviews result in correct review score, even if no review is active (=>0)
     */
    #[Group('reviews')]
    public function testCalculateWhenDeletingReviews(): void
    {
        $productAId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();

        $this->createProduct($productAId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 2.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productAId, true);
        $this->createReview($reviewBId, $pointsOnBReview, $productAId, true);

        $products = $this->productRepository->search(new Criteria([$productAId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertEquals(3.5, $productA->getRatingAverage());

        $this->deleteReview([['id' => $reviewAId]]);
        $products = $this->productRepository->search(new Criteria([$productAId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $productA = $products->get($productAId));
        static::assertEquals(2.0, $productA->getRatingAverage());
    }

    /**
     * tests that the full index works
     */
    #[Group('reviews')]
    public function testFullIndex(): void
    {
        $productId = Uuid::randomHex();
        $reviewAId = Uuid::randomHex();
        $reviewBId = Uuid::randomHex();

        $this->createProduct($productId);

        $pointsOnAReview = 5.0;
        $pointsOnBReview = 1.0;

        $this->createReview($reviewAId, $pointsOnAReview, $productId, true);
        $this->createReview($reviewBId, $pointsOnBReview, $productId, true);

        $sql = <<<'SQL'
            UPDATE product SET product.rating_average = 0;
SQL;
        $this->connection->executeStatement($sql);

        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());
        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals(0, $product->getRatingAverage());

        $this->productIndexer->handle(new EntityIndexingMessage([$productId]));
        $products = $this->productRepository->search(new Criteria([$productId]), $this->salesChannel->getContext());

        static::assertInstanceOf(ProductEntity::class, $product = $products->get($productId));
        static::assertEquals(3, $product->getRatingAverage());
    }

    /**
     * update data in review repository
     *
     * @param array<int, array<string, mixed>> $data
     */
    private function updateReview(array $data): void
    {
        $this->reviewRepository->upsert($data, $this->salesChannel->getContext());
    }

    /**
     * delete data in review repository
     *
     * @param array<int, array<string, mixed>> $data
     */
    private function deleteReview(array $data): void
    {
        $this->reviewRepository->delete($data, $this->salesChannel->getContext());
    }

    /**
     * creates a review in database
     */
    private function createReview(string $id, float $points, string $productId, bool $active): void
    {
        $customerId = Uuid::randomHex();
        $this->createCustomer($customerId);
        $salesChannelId = $this->salesChannel->getSalesChannelId();
        $languageId = Defaults::LANGUAGE_SYSTEM;
        $title = 'foo';

        $data = [
            'id' => $id,
            'productId' => $productId,
            'customerId' => $customerId,
            'salesChannelId' => $salesChannelId,
            'languageId' => $languageId,
            'status' => $active,
            'points' => $points,
            'content' => 'Lorem',
            'title' => $title,
        ];

        $this->reviewRepository->upsert([$data], $this->salesChannel->getContext());
    }

    /**
     * Creates a new product in the database.
     */
    private function createProduct(string $productId): void
    {
        $this->productRepository->create(
            [
                [
                    'id' => $productId,
                    'productNumber' => $productId,
                    'stock' => 1,
                    'name' => 'Test',
                    'active' => true,
                    'price' => [
                        [
                            'currencyId' => Defaults::CURRENCY,
                            'gross' => 100,
                            'net' => 9, 'linked' => false,
                        ],
                    ],
                    'manufacturer' => ['name' => 'test'],
                    'tax' => ['taxRate' => 19, 'name' => 'with id'],
                    'visibilities' => [
                        ['salesChannelId' => $this->salesChannel->getSalesChannelId(), 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                    ],
                    'categories' => [
                        ['id' => Uuid::randomHex(), 'name' => 'Clothing'],
                    ],
                ],
            ],
            $this->salesChannel->getContext()
        );
    }

    private function createCustomer(string $customerID): void
    {
        $email = 'foo@bar.de';
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerID,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => TestDefaults::HASHED_PASSWORD,
            'title' => 'Max',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];
        $this->customerRepository->create([$customer], Context::createDefaultContext());
    }
}
