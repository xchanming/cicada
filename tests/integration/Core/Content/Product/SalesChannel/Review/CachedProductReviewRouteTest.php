<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\Review;

use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewRouteResponse;
use Cicada\Core\Framework\Adapter\Cache\CacheTracer;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\StatsAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Group('cache')]
#[Group('store-api')]
class CachedProductReviewRouteTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        parent::setUp();

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    #[AfterClass]
    public function cleanup(): void
    {
        static::getContainer()->get('cache.object')
            ->invalidateTags([CachedProductReviewRoute::ALL_TAG]);
    }

    #[DataProvider('criteriaProvider')]
    public function testCriteria(Criteria $criteria): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $response = new ProductReviewRouteResponse(
            new EntitySearchResult('product_review', 0, new ProductReviewCollection(), null, $criteria, $context->getContext())
        );

        $core = $this->createMock(ProductReviewRoute::class);
        $core->expects(static::exactly(2))
            ->method('load')
            ->willReturn($response);

        $route = new CachedProductReviewRoute(
            $core,
            new TagAwareAdapter(new ArrayAdapter(100)),
            static::getContainer()->get(EntityCacheKeyGenerator::class),
            static::getContainer()->get(CacheTracer::class),
            static::getContainer()->get('event_dispatcher'),
            []
        );

        $ids = new IdsCollection();
        $route->load($ids->get('product'), new Request(), $context, $criteria);

        $route->load($ids->get('product'), new Request(), $context, $criteria);

        $criteria->setLimit(200);

        // check that provided criteria has other key
        $route->load($ids->get('product'), new Request(), $context, $criteria);
    }

    public static function criteriaProvider(): \Generator
    {
        yield 'Paginated criteria' => [(new Criteria())->setOffset(1)->setLimit(20)];
        yield 'Filtered criteria' => [(new Criteria())->addFilter(new EqualsFilter('active', true))];
        yield 'Post filtered criteria' => [(new Criteria())->addPostFilter(new EqualsFilter('active', true))];
        yield 'Aggregation criteria' => [(new Criteria())->addAggregation(new StatsAggregation('name', 'name'))];
        yield 'Query criteria' => [(new Criteria())->addQuery(new ScoreQuery(new EqualsFilter('active', true), 200))];
        yield 'Term criteria' => [(new Criteria())->setTerm('test')];
        yield 'Sorted criteria' => [(new Criteria())->addSorting(new FieldSorting('active'))];
    }
}
