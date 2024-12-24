<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric;

use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\RangeAggregation;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\RangeResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(RangeAggregation::class)]
class RangeAggregationTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $repository;

    private Context $context;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('product.repository');
        $this->context = Context::createDefaultContext();
    }

    /**
     * @return iterable<string, mixed>
     */
    public static function buildRangeKeyDataProvider(): iterable
    {
        yield 'empty from and empty to' => [null, null, '*-*'];
        yield 'empty from and to' => [null, 10, '*-10'];
        yield 'from and empty to' => [10, null, '10-*'];
    }

    #[DataProvider('buildRangeKeyDataProvider')]
    public function testBuildRangeKey(?float $from, ?float $to, string $expectedKey): void
    {
        $method = ReflectionHelper::getMethod(RangeAggregation::class, 'buildRangeKey');

        $aggregation = new RangeAggregation('test', 'test', []);

        static::assertEquals($expectedKey, $method->invoke($aggregation, $from, $to));
    }

    /**
     * @return array<string, array{rangesDefinition: mixed, rangesExpectedResult: mixed}>
     */
    public static function rangeAggregationDataProvider(): iterable
    {
        yield 'default ranges test cases' => [
            'rangesDefinition' => [
                [],
                ['key' => 'all'],
                ['key' => 'custom_key', 'from' => 0, 'to' => 15],
                ['to' => 10],
                ['from' => 11, 'to' => 20],
                ['from' => 20],
                ['from' => 10, 'to' => 10],
            ],
            'rangesExpectedResult' => [
                '*-*' => 8,
                'all' => 8,
                'custom_key' => 2,
                '*-10' => 1,
                '11-20' => 2,
                '20-*' => 4,
                '10-10' => 0,
            ],
        ];
    }

    /**
     * @param array<int, array<string, string|float>> $rangesDefinition
     * @param array<string, int> $rangesExpectedResult
     */
    #[DataProvider('rangeAggregationDataProvider')]
    public function testRangeAggregation(array $rangesDefinition, array $rangesExpectedResult): void
    {
        $ids = new IdsCollection();

        $data = [
            (new ProductBuilder($ids, 'a'))->price(5, 5)->build(),
            (new ProductBuilder($ids, 'b'))->price(10, 10)->build(),
            (new ProductBuilder($ids, 'c'))->price(15, 15)->build(),
            (new ProductBuilder($ids, 'd'))->price(15, 15)->build(),
            (new ProductBuilder($ids, 'e'))->price(25, 25)->build(),
            (new ProductBuilder($ids, 'f'))->price(26, 26)->build(),
            (new ProductBuilder($ids, 'g'))->price(30, 30)->build(),
            (new ProductBuilder($ids, 'h'))->price(100, 100)->build(),
        ];

        $this->repository->create($data, $this->context);

        $criteria = new Criteria();
        $criteria->addAggregation(
            new RangeAggregation(
                'test-range-aggregation',
                'price.gross',
                $rangesDefinition
            )
        );

        $aggregationCollection = $this->repository->aggregate($criteria, $this->context);
        static::assertTrue($aggregationCollection->has('test-range-aggregation'));
        static::assertInstanceOf(RangeResult::class, $aggregationCollection->get('test-range-aggregation'));

        $rangesResult = $aggregationCollection->get('test-range-aggregation')->getRanges();

        static::assertCount(\count($rangesDefinition), $rangesResult);
        foreach ($rangesResult as $key => $count) {
            static::assertArrayHasKey($key, $rangesExpectedResult);
            static::assertEquals($rangesExpectedResult[$key], $count);
        }
    }
}
