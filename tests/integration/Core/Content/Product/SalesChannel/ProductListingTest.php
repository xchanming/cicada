<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Content\Test\Product\SalesChannel\Fixture\ListingTestData;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\EntityResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Group('slow')]
class ProductListingTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    private string $categoryId;

    private ListingTestData $testData;

    private string $categoryStreamId;

    private Context $context;

    private string $productIdWidth100;

    private string $productIdWidth150;

    private string $salesChannelId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Context::createDefaultContext();

        $salesChannel = $this->createSalesChannel();
        $this->salesChannelId = $salesChannel['id'];

        $parent = $salesChannel['navigationCategoryId'];

        $this->categoryId = Uuid::randomHex();

        static::getContainer()->get('category.repository')
            ->create([['id' => $this->categoryId, 'name' => 'test', 'parentId' => $parent]], Context::createDefaultContext());

        $this->testData = new ListingTestData();

        $this->insertOptions();

        $this->insertProducts();

        $this->categoryStreamId = Uuid::randomHex();
    }

    public function testListing(): void
    {
        $request = new Request();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), $this->salesChannelId);

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($this->categoryId, $request, $context, new Criteria())
            ->getResult();
        $products = $listing->getEntities();

        static::assertCount(10, $products);
        static::assertFalse($products->has($this->testData->getId('product1')));

        self::assertVariationsInListing($products, [
            $this->testData->getId('product1-red-l-steel'),
            $this->testData->getId('product1-red-xl-steel'),
            $this->testData->getId('product1-red-l-iron'),
            $this->testData->getId('product1-red-xl-iron'),
        ]);
        self::assertVariationsInListing($products, [
            $this->testData->getId('product1-green-l-steel'),
            $this->testData->getId('product1-green-xl-steel'),
            $this->testData->getId('product1-green-l-iron'),
            $this->testData->getId('product1-green-xl-iron'),
        ]);

        // product 2 should display only the both color variants
        static::assertFalse($products->has($this->testData->getId('product2')));
        static::assertTrue($products->has($this->testData->getId('product2-green')));
        static::assertTrue($products->has($this->testData->getId('product2-red')));

        // product 3 has no variants
        static::assertTrue($products->has($this->testData->getId('product3')));

        self::assertVariationsInListing($products, [
            $this->testData->getId('product4-red-l-iron'),
            $this->testData->getId('product4-red-xl-iron'),
        ]);
        self::assertVariationsInListing($products, [
            $this->testData->getId('product4-red-l-steel'),
            $this->testData->getId('product4-red-xl-steel'),
        ]);
        self::assertVariationsInListing($products, [
            $this->testData->getId('product4-green-l-iron'),
            $this->testData->getId('product4-green-xl-iron'),
        ]);
        self::assertVariationsInListing($products, [
            $this->testData->getId('product4-green-l-steel'),
            $this->testData->getId('product4-green-xl-steel'),
        ]);

        self::assertVariationsInListing($products, [
            $this->testData->getId('product5-red'),
            $this->testData->getId('product5-green'),
        ]);

        $result = $listing->getAggregations()->get('properties');
        static::assertInstanceOf(EntityResult::class, $result);

        $options = $result->getEntities();
        static::assertInstanceOf(PropertyGroupCollection::class, $options);
        $ids = array_keys($options->getOptionIdMap());

        static::assertContains($this->testData->getId('green'), $ids);
        static::assertContains($this->testData->getId('red'), $ids);
        static::assertContains($this->testData->getId('xl'), $ids);
        static::assertContains($this->testData->getId('l'), $ids);
        static::assertContains($this->testData->getId('iron'), $ids);
        static::assertContains($this->testData->getId('steel'), $ids);
        static::assertFalse($options->has($this->testData->getId('yellow')));
        static::assertFalse($options->has($this->testData->getId('cotton')));
    }

    #[Group('slow')]
    public function testListingWithProductStream(): void
    {
        $this->createTestProductStreamEntity($this->categoryStreamId);
        $request = new Request();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), $this->salesChannelId);

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($this->categoryStreamId, $request, $context, new Criteria())
            ->getResult();

        static::assertSame(7, $listing->getTotal());
        static::assertFalse($listing->has($this->productIdWidth100));
        static::assertTrue($listing->has($this->productIdWidth150));
    }

    public function testListingWithProductStreamAndAdditionalCriteria(): void
    {
        $this->createTestProductStreamEntity($this->categoryStreamId);
        $request = new Request();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), $this->salesChannelId);

        $criteria = new Criteria();
        $criteria->addFilter(new ContainsFilter('name', 'Foo Bar'));

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($this->categoryStreamId, $request, $context, $criteria)
            ->getResult();

        static::assertSame(3, $listing->getTotal());
        $firstFilter = $listing->getCriteria()->getFilters()[0];
        static::assertInstanceOf(ContainsFilter::class, $firstFilter);
        static::assertEquals('name', $firstFilter->getField());
        static::assertEquals('Foo Bar', $firstFilter->getValue());
    }

    public function testNotFilterableProperty(): void
    {
        $request = new Request();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), $this->salesChannelId);

        $request->attributes->set('_route_params', [
            'navigationId' => $this->categoryId,
        ]);

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($this->categoryId, $request, $context, new Criteria())
            ->getResult();

        /** @var EntityResult<PropertyGroupCollection> $result */
        $result = $listing->getAggregations()->get('properties');
        $propertyGroups = $result->getEntities();

        $propertyGroupIds = [];

        foreach ($propertyGroups as $propertyGroup) {
            $propertyGroupIds[] = $propertyGroup->getId();
        }

        static::assertContains($this->testData->getId('color'), $propertyGroupIds);
        static::assertContains($this->testData->getId('size'), $propertyGroupIds);
        static::assertContains($this->testData->getId('material'), $propertyGroupIds);
        static::assertNotContains($this->testData->getId('class'), $propertyGroupIds);
    }

    /**
     * Small helper function which asserts the one of the provided pool ids are in the result set but the remaining ids are excluded.
     *
     * @param array<string> $pool
     */
    private static function assertVariationsInListing(ProductCollection $result, array $pool): void
    {
        $match = null;
        // find matching id
        foreach ($pool as $index => $id) {
            if ($result->has($id)) {
                $match = $id;
                unset($pool[$index]);

                break;
            }
        }
        // assert that one id found
        static::assertNotNull($match);

        // after one id found, assert that all other ids are not inside the result set
        foreach ($pool as $id) {
            static::assertFalse($result->has($id));
        }
    }

    private function insertProducts(): void
    {
        $this->createProduct(
            'product1',
            [
                [$this->testData->getId('red'), $this->testData->getId('green')],
                [$this->testData->getId('xl'), $this->testData->getId('l')],
                [$this->testData->getId('iron'), $this->testData->getId('steel')],
            ],
            [$this->testData->getId('color')]
        );

        $this->createProduct(
            'product2',
            [
                [$this->testData->getId('red'), $this->testData->getId('green')],
            ],
            [$this->testData->getId('color')]
        );

        $this->createProduct('product3', [], []);

        $this->createProduct(
            'product4',
            [
                [$this->testData->getId('red'), $this->testData->getId('green')],
                [$this->testData->getId('xl'), $this->testData->getId('l')],
                [$this->testData->getId('iron'), $this->testData->getId('steel')],
            ],
            [$this->testData->getId('color'), $this->testData->getId('material')]
        );

        $this->createProduct(
            'product5',
            [
                [$this->testData->getId('red'), $this->testData->getId('green')],
            ],
            []
        );
    }

    /**
     * @param array<array<string>> $options
     * @param array<string> $listingGroups
     */
    private function createProduct(string $key, array $options, array $listingGroups): void
    {
        $config = [];
        foreach ($listingGroups as $groupId) {
            $config[] = [
                'id' => $groupId,
                'expressionForListings' => true,
                'representation' => 'box', // box, select, image, color
            ];
        }

        $configurator = [];
        foreach ($options as $grouped) {
            foreach ($grouped as $optionId) {
                $configurator[] = ['optionId' => $optionId];
            }
        }

        $id = $this->testData->createId($key);
        $data = [
            [
                'id' => $id,
                'variantListingConfig' => [
                    'configuratorGroupConfig' => $config,
                ],
                'productNumber' => $id,
                'manufacturer' => ['name' => 'test'],
                'tax' => ['taxRate' => 19, 'name' => 'test'],
                'stock' => 10,
                'name' => $key,
                'active' => true,
                'price' => [
                    ['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => true],
                ],
                'categories' => [
                    ['id' => $this->categoryId],
                ],
                'configuratorSettings' => $configurator,
                'visibilities' => [
                    [
                        'salesChannelId' => $this->salesChannelId,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ];

        if (!empty($options)) {
            foreach ($this->combos($options) as $index => $combination) {
                $variantKey = $key . '-' . implode('-', $this->testData->getKeyList($combination));

                $data[] = [
                    'id' => $this->testData->createId($variantKey),
                    'productNumber' => $key . '.' . $index,
                    'stock' => 10,
                    'name' => $variantKey,
                    'active' => true,
                    'parentId' => $this->testData->getId($key),
                    'options' => array_map(static fn ($id) => ['id' => $id], $combination),
                ];
            }
        }

        $repo = static::getContainer()->get('product.repository');

        $repo->create($data, Context::createDefaultContext());
    }

    /**
     * Rec. Function to find all possible combinations of $data input
     *
     * @param array<array<string>> $data
     * @param array<array<string>> $all
     * @param array<string> $group
     *
     * @return array<array<string>>
     */
    private function combos(array $data, &$all = [], $group = [], ?string $val = null, int $i = 0): array
    {
        if (isset($val)) {
            $group[] = $val;
        }
        if ($i >= \count($data)) {
            $all[] = $group;
        } else {
            foreach ($data[$i] as $v) {
                $this->combos($data, $all, $group, $v, $i + 1);
            }
        }

        return $all;
    }

    private function insertOptions(): void
    {
        $data = [
            [
                'id' => $this->testData->createId('color'),
                'name' => 'color',
                'options' => [
                    ['id' => $this->testData->createId('green'), 'name' => 'green'],
                    ['id' => $this->testData->createId('red'), 'name' => 'red'],
                    ['id' => $this->testData->createId('yellow'), 'name' => 'red'],
                ],
            ],
            [
                'id' => $this->testData->createId('size'),
                'name' => 'size',
                'options' => [
                    ['id' => $this->testData->createId('xl'), 'name' => 'XL'],
                    ['id' => $this->testData->createId('l'), 'name' => 'L'],
                ],
            ],
            [
                'id' => $this->testData->createId('material'),
                'name' => 'material',
                'options' => [
                    ['id' => $this->testData->createId('iron'), 'name' => 'iron'],
                    ['id' => $this->testData->createId('steel'), 'name' => 'steel'],
                    ['id' => $this->testData->createId('cotton'), 'name' => 'steel'],
                ],
            ],
            [
                'id' => $this->testData->createId('class'),
                'name' => 'class',
                'options' => [
                    ['id' => $this->testData->createId('first'), 'name' => 'first'],
                    ['id' => $this->testData->createId('business'), 'name' => 'business'],
                    ['id' => $this->testData->createId('coach'), 'name' => 'coach'],
                ],
            ],
        ];

        static::getContainer()->get('property_group.repository')->create($data, Context::createDefaultContext());
    }

    private function createTestProductStreamEntity(string $categoryStreamId): void
    {
        $streamId = Uuid::randomHex();

        $randomProductIds = implode('|', array_column($this->createProducts(), 'id'));

        $stream = [
            'id' => $streamId,
            'name' => 'testStream',
            'filters' => [
                [
                    'type' => 'multi',
                    'queries' => [
                        [
                            'type' => 'equalsAny',
                            'field' => 'product.id',
                            'value' => $randomProductIds,
                        ],
                        [
                            'type' => 'range',
                            'field' => 'product.width',
                            'parameters' => [
                                'gte' => 120,
                                'lte' => 180,
                            ],
                        ],
                    ],
                    'operator' => 'AND',
                ],
            ],
        ];
        $productRepository = static::getContainer()->get('product_stream.repository');
        $productRepository->create([$stream], $this->context);

        static::getContainer()->get('category.repository')
            ->create([['id' => $categoryStreamId, 'productStreamId' => $streamId, 'name' => 'test', 'parentId' => null, 'productAssignmentType' => 'product_stream']], Context::createDefaultContext());
    }

    /**
     * @return array<array{id: string, productNumber: string, width: string, stock: int, name: string}>
     */
    private function createProducts(): array
    {
        $ids = new IdsCollection();
        $ids->create('manufacturer');
        $ids->create('taxId');

        $productRepository = static::getContainer()->get('product.repository');
        $salesChannelId = $this->salesChannelId;
        $products = [];

        $widths = [
            '100',
            '110',
            '120',
            '130',
            '140',
            '150',
            '160',
            '170',
            '180',
            '190',
        ];

        $names = [
            'Wooden Heavy Magma',
            'Small Plastic Prawn Leather',
            'Fantastic Marble Megahurts',
            'Foo Bar Aerodynamic Iron Viagreat',
            'Foo Bar Awesome Bronze Sulpha Quik',
            'Foo Bar Aerodynamic Silk Ideoswitch',
            'Heavy Duty Wooden Magnina',
            'Incredible Wool Q-lean',
            'Heavy Duty Cotton Gristle Chips',
            'Heavy Steel Hot Magma',
        ];

        for ($i = 0; $i < 10; ++$i) {
            $products[] = [
                'id' => Uuid::randomHex(),
                'productNumber' => Uuid::randomHex(),
                'width' => $widths[$i],
                'stock' => 1,
                'name' => $names[$i],
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => false]],
                'manufacturer' => ['id' => $ids->get('manufacturer'), 'name' => 'test'],
                'tax' => ['id' => $ids->get('taxId'), 'taxRate' => 17, 'name' => 'with id'],
                'visibilities' => [
                    ['salesChannelId' => $salesChannelId, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ];
        }

        $this->productIdWidth100 = $products[0]['id'];
        $this->productIdWidth150 = $products[5]['id'];

        $productRepository->create($products, $this->context);

        return $products;
    }
}
