<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Salutation;

use Cicada\Core\Framework\Adapter\Cache\CacheTracer;
use Cicada\Core\Framework\Context;
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
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Salutation\SalesChannel\CachedSalutationRoute;
use Cicada\Core\System\Salutation\SalesChannel\SalutationRoute;
use Cicada\Core\System\Salutation\SalesChannel\SalutationRouteResponse;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[Group('cache')]
#[Group('store-api')]
class CachedSalutationRouteTest extends TestCase
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
            ->invalidateTags([CachedSalutationRoute::ALL_TAG]);
    }

    #[DataProvider('criteriaProvider')]
    public function testCriteria(Criteria $criteria): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $response = new SalutationRouteResponse(
            new EntitySearchResult('salutation', 0, new SalutationCollection(), null, $criteria, $context->getContext())
        );

        $core = $this->createMock(SalutationRoute::class);
        $core->expects(static::exactly(2))
            ->method('load')
            ->willReturn($response);

        $route = new CachedSalutationRoute(
            $core,
            new TagAwareAdapter(new ArrayAdapter(100)),
            static::getContainer()->get(EntityCacheKeyGenerator::class),
            static::getContainer()->get(CacheTracer::class),
            static::getContainer()->get('event_dispatcher'),
            [],
        );

        $route->load(new Request(), $context, $criteria);

        $route->load(new Request(), $context, $criteria);

        $criteria->setLimit(200);

        // check that provided criteria has other key
        $route->load(new Request(), $context, $criteria);
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

    #[DataProvider('invalidationProvider')]
    public function testInvalidation(\Closure $before, \Closure $after, int $calls): void
    {
        static::getContainer()->get('cache.object')
            ->invalidateTags([CachedSalutationRoute::ALL_TAG]);

        $route = static::getContainer()->get(SalutationRoute::class);

        static::assertInstanceOf(CachedSalutationRoute::class, $route);

        $dispatcher = static::getContainer()->get('event_dispatcher');
        $listener = $this->getMockBuilder(CallableClass::class)->getMock();

        $listener->expects(static::exactly($calls))->method('__invoke');
        $this->addEventListener($dispatcher, 'salutation.loaded', $listener);

        $before(static::getContainer());

        $route->load(new Request(), $this->context, new Criteria());
        $route->load(new Request(), $this->context, new Criteria());

        $after(static::getContainer());

        $route->load(new Request(), $this->context, new Criteria());
        $route->load(new Request(), $this->context, new Criteria());
    }

    public static function invalidationProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Cache invalidated if created salutation assigned' => [
            function (): void {
            },
            function (ContainerInterface $container) use ($ids): void {
                $data = [
                    'id' => $ids->get('salutation'),
                    'displayName' => 'test',
                    'letterName' => 'test',
                    'salutationKey' => 'test',
                ];

                $container->get('salutation.repository')->create([$data], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache invalidated if updated salutation assigned' => [
            function (ContainerInterface $container) use ($ids): void {
                $data = [
                    'id' => $ids->get('salutation'),
                    'displayName' => 'test',
                    'letterName' => 'test',
                    'salutationKey' => 'test',
                ];

                $container->get('salutation.repository')->create([$data], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $data = [
                    'id' => $ids->get('salutation'),
                    'displayName' => 'update',
                ];

                $container->get('salutation.repository')->update([$data], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache invalidated if deleted salutation assigned' => [
            function (ContainerInterface $container) use ($ids): void {
                $data = [
                    'id' => $ids->get('salutation'),
                    'displayName' => 'test',
                    'letterName' => 'test',
                    'salutationKey' => 'test',
                ];

                $container->get('salutation.repository')->create([$data], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $data = [
                    'id' => $ids->get('salutation'),
                ];

                $container->get('salutation.repository')->delete([$data], Context::createDefaultContext());
            },
            2,
        ];
    }
}
