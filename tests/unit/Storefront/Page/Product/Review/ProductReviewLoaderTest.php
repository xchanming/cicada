<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\Product\Review;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewLoader as CoreProductReviewLoader;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewRouteResponse;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use Cicada\Storefront\Page\Product\Review\ProductReviewLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ProductReviewLoader::class)]
class ProductReviewLoaderTest extends TestCase
{
    protected function setUp(): void
    {
        Feature::skipTestIfActive('v6.7.0.0', $this);
    }

    public function testExceptionWithoutProductId(): void
    {
        $request = new Request([], [], []);
        $salesChannelContext = $this->getSalesChannelContext();

        $productReviewRouteMock = $this->createMock(ProductReviewRoute::class);

        $productReviewLoader = $this->getProductReviewLoader($productReviewRouteMock);

        $this->expectException(RoutingException::class);

        $productReviewLoader->load($request, $salesChannelContext);
    }

    public function testItLoadsReviewsWithProductId(): void
    {
        $reviewId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $request = new Request([], [], ['productId' => $productId]);
        $salesChannelContext = $this->getSalesChannelContext(false);

        $review = $this->getReviewEntity($reviewId);

        $reviews = new ProductReviewCollection([
            $review,
        ]);

        $productReviewRouteMock = $this->createMock(ProductReviewRoute::class);
        $productReviewLoader = $this->getProductReviewLoader($productReviewRouteMock);

        $reviewResult = $this->getDefaultResult($reviews, $request, $salesChannelContext);

        $productReviewRouteMock
            ->method('load')
            ->willReturn(
                new ProductReviewRouteResponse($reviewResult)
            );

        $result = $productReviewLoader->load($request, $salesChannelContext);

        static::assertInstanceOf(ProductReviewEntity::class, $result->first());
        static::assertEquals($result->first()->getId(), $reviewId);
        static::assertCount(1, $result);
        static::assertNull($result->getCustomerReview());
    }

    public function testItLoadsReviewsPagination(): void
    {
        $reviewId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $request = new Request([], [], ['productId' => $productId, 'p' => 2]);
        $salesChannelContext = $this->getSalesChannelContext(false);

        $review = $this->getReviewEntity($reviewId);

        $reviews = new ProductReviewCollection([
            $review,
        ]);

        $productReviewRouteMock = $this->createMock(ProductReviewRoute::class);
        $productReviewLoader = $this->getProductReviewLoader($productReviewRouteMock);

        $reviewResult = $this->getDefaultResult($reviews, $request, $salesChannelContext);

        $criteria = $this->createCriteria($request, $salesChannelContext);

        $productReviewRouteMock
            ->method('load')
            ->with($productId, $request, $salesChannelContext, $criteria)
            ->willReturn(
                new ProductReviewRouteResponse($reviewResult)
            );

        $result = $productReviewLoader->load($request, $salesChannelContext);

        $firstResult = $result->first();
        static::assertInstanceOf(ProductReviewEntity::class, $firstResult);
        static::assertEquals($firstResult->getId(), $reviewId);
        static::assertEquals($result->getCriteria()->getOffset(), 10);
        static::assertCount(1, $result);
        static::assertNull($result->getCustomerReview());
    }

    public function testNegativeOffsetDefaultsToZero(): void
    {
        $reviewId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $request = new Request([], [], ['productId' => $productId, 'p' => -2]);
        $salesChannelContext = $this->getSalesChannelContext(false);

        $review = $this->getReviewEntity($reviewId);

        $reviews = new ProductReviewCollection([
            $review,
        ]);

        $productReviewRouteMock = $this->createMock(ProductReviewRoute::class);
        $productReviewLoader = $this->getProductReviewLoader($productReviewRouteMock);

        $reviewResult = $this->getDefaultResult($reviews, $request, $salesChannelContext);

        $criteria = $this->createCriteria($request, $salesChannelContext);

        $productReviewRouteMock
            ->method('load')
            ->with($productId, $request, $salesChannelContext, $criteria)
            ->willReturn(
                new ProductReviewRouteResponse($reviewResult)
            );

        $result = $productReviewLoader->load($request, $salesChannelContext);

        static::assertInstanceOf(ProductReviewEntity::class, $result->first());
        static::assertEquals($result->first()->getId(), $reviewId);
        static::assertEquals($result->getCriteria()->getOffset(), 0);
        static::assertCount(1, $result);
        static::assertNull($result->getCustomerReview());
    }

    public function testItLoadsReviewsWithParentId(): void
    {
        $reviewId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $request = new Request([], [], ['productId' => $productId, 'parentId' => $productId, 'sort' => 'points', 'language' => 'filter-language']);
        $salesChannelContext = $this->getSalesChannelContext();

        $review = $this->getReviewEntity($reviewId);

        $reviews = new ProductReviewCollection([
            $review,
        ]);

        $productReviewRouteMock = $this->createMock(ProductReviewRoute::class);
        $productReviewLoader = $this->getProductReviewLoader($productReviewRouteMock);

        $reviewResult = $this->getDefaultResult($reviews, $request, $salesChannelContext);

        $productReviewRouteMock
            ->method('load')
            ->willReturn(
                new ProductReviewRouteResponse($reviewResult)
            );

        $result = $productReviewLoader->load($request, $salesChannelContext);

        static::assertInstanceOf(ProductReviewEntity::class, $result->first());
        static::assertEquals($reviewId, $result->first()->getId());
        static::assertCount(1, $result);
        static::assertEquals([new FieldSorting('points', 'DESC')], $result->getCriteria()->getSorting());
        static::assertNotNull($result->getCustomerReview());
    }

    public function testItLoadsReviewsWithPointsFilter(): void
    {
        $reviewId = Uuid::randomHex();
        $productId = Uuid::randomHex();
        $request = new Request([], [], ['productId' => $productId, 'points' => ['4', 'gg']]);
        $salesChannelContext = $this->getSalesChannelContext();

        $review = $this->getReviewEntity($reviewId);

        $reviews = new ProductReviewCollection([
            $review,
        ]);

        $productReviewRouteMock = $this->createMock(ProductReviewRoute::class);
        $productReviewLoader = $this->getProductReviewLoader($productReviewRouteMock);

        $reviewResult = $this->getDefaultResult($reviews, $request, $salesChannelContext);

        $productReviewRouteMock
            ->method('load')
            ->willReturn(
                new ProductReviewRouteResponse($reviewResult)
            );

        $result = $productReviewLoader->load($request, $salesChannelContext);

        static::assertInstanceOf(ProductReviewEntity::class, $result->first());
        static::assertEquals($result->first()->getId(), $reviewId);
        static::assertCount(1, $result);
    }

    private function getReviewEntity(string $reviewId): ProductReviewEntity
    {
        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $review = new ProductReviewEntity();
        $review->setId($reviewId);
        $review->setUniqueIdentifier($reviewId);
        $review->setCustomer($customer);

        return $review;
    }

    private function getProductReviewLoader(
        ProductReviewRoute $productReviewRouteMock
    ): ProductReviewLoader {
        $coreProductReviewLoader = new CoreProductReviewLoader(
            $productReviewRouteMock,
            $this->createMock(SystemConfigService::class),
            $this->createMock(EventDispatcherInterface::class)
        );

        return new ProductReviewLoader(
            $coreProductReviewLoader,
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    /**
     * @return EntitySearchResult<ProductReviewCollection>
     */
    private function getDefaultResult(
        ProductReviewCollection $reviews,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): EntitySearchResult {
        $criteria = $this->createCriteria($request, $salesChannelContext);

        return new EntitySearchResult(
            ProductReviewDefinition::ENTITY_NAME,
            1,
            $reviews,
            new AggregationResultCollection(
                [
                    'ratingMatrix' => new TermsResult('ratingMatrix', []),
                ]
            ),
            $criteria,
            Context::createDefaultContext()
        );
    }

    private function getSalesChannelContext(bool $setCustomer = true): SalesChannelContext
    {
        $salesChannelEntity = new SalesChannelEntity();
        $salesChannelEntity->setId('salesChannelId');

        $customer = null;

        if ($setCustomer) {
            $customer = new CustomerEntity();
            $customer->setId(Uuid::randomHex());
        }

        return Generator::createSalesChannelContext(
            salesChannel: $salesChannelEntity,
            customer: $customer,
            createCustomer: $setCustomer
        );
    }

    private function createCriteria(Request $request, SalesChannelContext $context): Criteria
    {
        $limit = (int) $request->get('limit', 10);
        $page = (int) $request->get('p', 1);
        $offset = max(0, $limit * ($page - 1));

        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        $sorting = new FieldSorting('createdAt', 'DESC');
        if ($request->get('sort', 'createdAt') === 'points') {
            $sorting = new FieldSorting('points', 'DESC');
        }

        $criteria->addSorting($sorting);

        if ($request->get('language') === 'filter-language') {
            $criteria->addPostFilter(
                new EqualsFilter('languageId', $context->getContext()->getLanguageId())
            );
        } else {
            $criteria->addAssociation('language.translationCode.code');
        }

        $reviewFilters[] = new EqualsFilter('status', true);

        if ($context->getCustomer() !== null) {
            $reviewFilters[] = new EqualsFilter('customerId', $context->getCustomer()->getId());
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'customer-login-filter',
                new TermsAggregation('ratingMatrix', 'points'),
                [
                    new MultiFilter(MultiFilter::CONNECTION_OR, $reviewFilters),
                ]
            )
        );

        return $criteria;
    }
}
