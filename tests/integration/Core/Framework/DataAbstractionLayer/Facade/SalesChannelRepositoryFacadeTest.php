<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Facade;

use Cicada\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Exception\ApiProtectionException;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\SalesChannelRepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Script\Execution\Script;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\Framework\Struct\ArrayStruct;
use Cicada\Core\Framework\Test\Script\Execution\SalesChannelTestHook;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\Test\AppSystemTestBehaviour;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SalesChannelRepositoryFacadeTest extends TestCase
{
    use AppSystemTestBehaviour;
    use IntegrationTestBehaviour;

    private IdsCollection $ids;

    private SalesChannelRepositoryFacadeHookFactory $factory;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        $this->factory = static::getContainer()->get(SalesChannelRepositoryFacadeHookFactory::class);
        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    /**
     * @param array<string, array<int, mixed>> $criteria
     * @param callable(EntitySearchResult<EntityCollection<Entity>>): void $expectation
     */
    #[DataProvider('testCases')]
    public function testFacade(array $criteria, string $method, IdsCollection $ids, callable $expectation): void
    {
        $this->ids = $ids;
        $this->createProducts();

        $facade = $this->factory->factory(
            new SalesChannelTestHook('test', $this->context),
            new Script('test', '', new \DateTimeImmutable())
        );

        $result = $facade->$method('product', $criteria); /* @phpstan-ignore-line */

        $expectation($result);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function testCases(): array
    {
        $ids = new IdsCollection();

        return [
            'testEmptySearch' => [
                [],
                'search',
                $ids,
                function (EntitySearchResult $result): void {
                    static::assertCount(3, $result);
                },
            ],
            'testSearchFilter' => [
                [
                    'filter' => [
                        ['type' => 'equals', 'field' => 'childCount', 'value' => 0],
                    ],
                ],
                'search',
                $ids,
                function (EntitySearchResult $result) use ($ids): void {
                    static::assertCount(1, $result);
                    static::assertContains($ids->get('p3'), $result->getIds());
                },
            ],
            'testSearchRead' => [
                [
                    'ids' => [$ids->get('p1'), $ids->get('p2')],
                ],
                'search',
                $ids,
                function (EntitySearchResult $result) use ($ids): void {
                    static::assertCount(1, $result);
                    static::assertContains($ids->get('p1'), $result->getIds());
                },
            ],
            'testSearchAggregation' => [
                [
                    'aggregations' => [
                        ['name' => 'sum', 'type' => 'sum', 'field' => 'childCount'],
                    ],
                ],
                'search',
                $ids,
                function (EntitySearchResult $result): void {
                    static::assertCount(3, $result);
                    $agg = $result->getAggregations()->get('sum');
                    static::assertInstanceOf(SumResult::class, $agg);
                    static::assertEquals(1, $agg->getSum());
                },
            ],
            'testSearchSort' => [
                [
                    'sort' => [['field' => 'id']],
                ],
                'search',
                $ids,
                function (EntitySearchResult $result): void {
                    $actual = $result->getIds();

                    $expected = $actual;
                    sort($expected);

                    static::assertEquals($expected, array_values($actual));
                },
            ],
            'testEmptySearchIds' => [
                [],
                'ids',
                $ids,
                function (IdSearchResult $result): void {
                    static::assertCount(3, $result->getIds());
                },
            ],
            'testSearchIdsFilter' => [
                [
                    'filter' => [
                        ['type' => 'equals', 'field' => 'childCount', 'value' => 0],
                    ],
                ],
                'ids',
                $ids,
                function (IdSearchResult $result) use ($ids): void {
                    static::assertCount(1, $result->getIds());
                    static::assertContains($ids->get('p3'), $result->getIds());
                },
            ],
            'testEmptyAggregation' => [
                [],
                'aggregate',
                $ids,
                function (AggregationResultCollection $result): void {
                    static::assertCount(0, $result);
                },
            ],
            'testAggregation' => [
                [
                    'aggregations' => [
                        ['name' => 'sum', 'type' => 'sum', 'field' => 'childCount'],
                    ],
                ],
                'aggregate',
                $ids,
                function (AggregationResultCollection $result): void {
                    static::assertCount(1, $result);
                    $agg = $result->get('sum');
                    static::assertInstanceOf(SumResult::class, $agg);
                    static::assertEquals(1, $agg->getSum());
                },
            ],
        ];
    }

    public function testIntegrationCase(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $page = new ArrayStruct();
        $hook = new SalesChannelTestHook(
            'store-search-by-id',
            $this->context,
            [
                'productId' => $this->ids->get('p1'),
                'page' => $page,
            ],
            [
                SalesChannelRepositoryFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        static::assertTrue($page->hasExtension('myProduct'));
        $product = $page->getExtension('myProduct');
        static::assertInstanceOf(SalesChannelProductEntity::class, $product);
        static::assertEquals($this->ids->get('p1'), $product->getId());
    }

    public function testSearchWithFilterIntegration(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $page = new ArrayStruct();
        $hook = new SalesChannelTestHook(
            'store-filter',
            $this->context,
            [
                'page' => $page,
            ],
            [
                SalesChannelRepositoryFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        static::assertTrue($page->hasExtension('myProduct'));
        $product = $page->getExtension('myProduct');
        static::assertInstanceOf(SalesChannelProductEntity::class, $product);
        static::assertEquals($this->ids->get('p1'), $product->getId());
    }

    public function testSearchWithAssociationIntegration(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $page = new ArrayStruct();
        $hook = new SalesChannelTestHook(
            'store-association',
            $this->context,
            [
                'productId' => $this->ids->get('p1'),
                'page' => $page,
            ],
            [
                SalesChannelRepositoryFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        static::assertTrue($page->hasExtension('myProduct'));
        $product = $page->getExtension('myProduct');
        static::assertInstanceOf(SalesChannelProductEntity::class, $product);
        static::assertEquals($this->ids->get('p1'), $product->getId());
        static::assertInstanceOf(ProductManufacturerEntity::class, $product->getManufacturer());

        $manufacturer = $page->getExtension('myManufacturer');
        static::assertInstanceOf(ProductManufacturerEntity::class, $manufacturer);
        static::assertEquals($this->ids->get('m1'), $manufacturer->getId());
    }

    public function testSearchIdsIntegration(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $page = new ArrayStruct();
        $hook = new SalesChannelTestHook(
            'store-search-ids',
            $this->context,
            [
                'page' => $page,
            ],
            [
                SalesChannelRepositoryFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        static::assertTrue($page->hasExtension('myProductIds'));
        $extension = $page->getExtension('myProductIds');
        static::assertInstanceOf(ArrayStruct::class, $extension);
        static::assertEquals([$this->ids->get('p1')], $extension->get('ids'));
    }

    public function testAggregateIntegration(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $this->installApp(__DIR__ . '/_fixtures/apps/pageLoadedExample');

        $page = new ArrayStruct();
        $hook = new SalesChannelTestHook(
            'store-aggregate',
            $this->context,
            [
                'page' => $page,
            ],
            [
                SalesChannelRepositoryFacadeHookFactory::class,
            ]
        );

        static::getContainer()->get(ScriptExecutor::class)->execute($hook);

        static::assertTrue($page->hasExtension('myProductAggregations'));
        $extension = $page->getExtension('myProductAggregations');
        static::assertInstanceOf(ArrayStruct::class, $extension);
        static::assertEquals(1, $extension->get('sum'));
    }

    public function testItThrowsForNotApiAwareField(): void
    {
        $this->ids = new IdsCollection();
        $this->createProducts();

        $facade = $this->factory->factory(
            new SalesChannelTestHook('test', $this->context),
            new Script('test', '', new \DateTimeImmutable())
        );

        $criteria = [
            'aggregations' => [
                ['name' => 'sum', 'type' => 'sum', 'field' => 'autoIncrement'],
            ],
        ];

        static::expectException(ApiProtectionException::class);
        $facade->search('product', $criteria);
    }

    private function createProducts(): void
    {
        $taxId = $this->getExistingTaxId();
        $this->ids->set('t1', $taxId);

        $product1 = (new ProductBuilder($this->ids, 'p1'))
            ->price(100)
            ->visibility()
            ->manufacturer('m1')
            ->variant(
                (new ProductBuilder($this->ids, 'v1.1'))
                    ->build()
            );

        $product2 = (new ProductBuilder($this->ids, 'p2'))
            ->price(200)
            ->visibility()
            ->active(false);

        $product3 = (new ProductBuilder($this->ids, 'p3'))
            ->visibility()
            ->price(300);

        static::getContainer()->get('product.repository')->create([
            $product1->build(),
            $product2->build(),
            $product3->build(),
        ], $this->context->getContext());
    }

    private function installApp(string $appDir): string
    {
        $this->loadAppsFromDir($appDir);

        $appId = static::getContainer()->get('app.repository')->searchIds(new Criteria(), Context::createDefaultContext())->firstId();
        static::assertIsString($appId);

        return $appId;
    }

    private function getExistingTaxId(): string
    {
        /** @var EntityRepository<TaxCollection> $taxRepository */
        $taxRepository = static::getContainer()->get('tax.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Standard rate'));

        $taxId = $taxRepository->searchIds($criteria, $this->context->getContext())->firstId();
        static::assertIsString($taxId);

        return $taxId;
    }
}
