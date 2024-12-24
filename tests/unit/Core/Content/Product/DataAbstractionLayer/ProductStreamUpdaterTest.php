<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\DataAbstractionLayer;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductStreamMappingIndexingMessage;
use Cicada\Core\Content\Product\DataAbstractionLayer\ProductStreamUpdater;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Indexing\ManyToManyIdFieldUpdater;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[CoversClass(ProductStreamUpdater::class)]
class ProductStreamUpdaterTest extends TestCase
{
    /**
     * @param string[] $ids
     * @param array<int, array<string, bool|string>> $filters
     */
    #[DataProvider('filterProvider')]
    public function testCriteriaWithUpdateProducts(array $ids, array $filters, Criteria $criteria): void
    {
        $context = Context::createDefaultContext();

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(static::once())
            ->method('fetchAllAssociative')
            ->willReturn($filters);

        $repository = new StaticEntityRepository([
            function (Criteria $actualCriteria, Context $actualContext) use ($criteria, $context, $ids): array {
                static::assertEquals($criteria, $actualCriteria);
                static::assertEquals($context, $actualContext);

                return $ids;
            },
        ]);

        $updater = new ProductStreamUpdater(
            $connection,
            new ProductDefinition(),
            $repository,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(ManyToManyIdFieldUpdater::class)
        );

        $updater->updateProducts($ids, $context);
    }

    /**
     * @param string[] $ids
     * @param array<int, array<string, bool|string>> $filters
     */
    #[DataProvider('filterProvider')]
    public function testCriteriaWithHandle(array $ids, array $filters, Criteria $criteria): void
    {
        $context = Context::createDefaultContext();
        $context->setConsiderInheritance(true);

        $message = new ProductStreamMappingIndexingMessage(Uuid::randomHex());

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(static::once())
            ->method('fetchOne')
            ->willReturn(current(array_column($filters, 'api_filter')));

        $criteria->setLimit(150);
        $criteria->addSorting(new FieldSorting('autoIncrement'));
        $filters = $criteria->getFilters();
        array_pop($filters);
        $criteria->resetFilters();
        $criteria->addFilter(...$filters);
        $criteria->setFilter('increment', new RangeFilter('autoIncrement', [RangeFilter::GTE => 0]));

        $definition = new ProductDefinition();
        $repository = new StaticEntityRepository([
            function (Criteria $actualCriteria, Context $actualContext) use ($criteria, $context, $ids): array {
                static::assertEquals($criteria, $actualCriteria);
                static::assertEquals($context, $actualContext);

                return $ids;
            },
            fn () => [],
        ], $definition);

        $manyToManyFieldUpdater = $this->createMock(ManyToManyIdFieldUpdater::class);
        $manyToManyFieldUpdater
            ->expects(static::once())
            ->method('update')
            ->with($definition->getEntityName(), $ids, Context::createDefaultContext(), 'streamIds');

        $updater = new ProductStreamUpdater(
            $connection,
            $definition,
            $repository,
            $this->createMock(MessageBusInterface::class),
            $manyToManyFieldUpdater
        );

        $updater->handle($message);
    }

    /**
     * @return iterable<string, array<int, array<int, array<string, bool|string>|string>|Criteria>>
     */
    public static function filterProvider(): iterable
    {
        $id = Uuid::randomHex();

        yield 'Active filter' => [
            [$id],
            [
                [
                    'id' => Uuid::randomHex(),
                    'api_filter' => json_encode([[
                        'type' => 'equals',
                        'field' => 'active',
                        'value' => '1',
                    ]]),
                ],
            ],
            (new Criteria())->addFilter(
                new EqualsFilter('product.active', true),
                new EqualsAnyFilter('id', [$id])
            ),
        ];

        yield 'Price filter' => [
            [$id],
            [
                [
                    'id' => Uuid::randomHex(),
                    'api_filter' => json_encode([[
                        'type' => 'range',
                        'field' => 'product.cheapestPrice',
                        'parameters' => [
                            'lte' => 50,
                        ],
                    ]]),
                ],
            ],
            (new Criteria())->addFilter(
                new MultiFilter(MultiFilter::CONNECTION_OR, [
                    new RangeFilter('product.price', [RangeFilter::LTE => 50]),
                    new RangeFilter('product.prices.price', [RangeFilter::LTE => 50]),
                ]),
                new EqualsAnyFilter('id', [$id])
            ),
        ];

        yield 'Nested price filter' => [
            [$id],
            [
                [
                    'id' => Uuid::randomHex(),
                    'api_filter' => json_encode([[
                        'type' => 'multi',
                        'operator' => 'AND',
                        'queries' => [[
                            'type' => 'range',
                            'field' => 'product.cheapestPrice',
                            'parameters' => [
                                'lte' => 50,
                            ],
                        ]],
                    ]]),
                ],
            ],
            (new Criteria())->addFilter(
                new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new MultiFilter(MultiFilter::CONNECTION_OR, [
                        new RangeFilter('product.price', [RangeFilter::LTE => 50]),
                        new RangeFilter('product.prices.price', [RangeFilter::LTE => 50]),
                    ]),
                ]),
                new EqualsAnyFilter('id', [$id])
            ),
        ];

        yield 'Nested price percentage filter' => [
            [$id],
            [
                [
                    'id' => Uuid::randomHex(),
                    'api_filter' => json_encode([[
                        'type' => 'multi',
                        'operator' => 'AND',
                        'queries' => [[
                            'type' => 'range',
                            'field' => 'cheapestPrice.percentage',
                            'parameters' => [
                                'lte' => 50,
                            ],
                        ]],
                    ]]),
                ],
            ],
            (new Criteria())->addFilter(
                new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new MultiFilter(MultiFilter::CONNECTION_OR, [
                        new RangeFilter('product.price.percentage', [RangeFilter::LTE => 50]),
                        new RangeFilter('product.prices.price.percentage', [RangeFilter::LTE => 50]),
                    ]),
                ]),
                new EqualsAnyFilter('id', [$id])
            ),
        ];
    }
}
