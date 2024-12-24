<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\Listing;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\EntityResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\MaxResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\StatsResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ProductListingRoute::class)]
#[Group('store-api')]
class ProductListingRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private string $productId;

    /**
     * @var array<string>
     */
    private array $groupIds;

    /**
     * @var array<string>
     */
    private array $optionIds;

    /**
     * @var array<string>
     */
    private array $variantIds;

    private EntityRepository $categoryRepository;

    private EntityRepository $productRepository;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->createSalesChannelContext(['id' => $this->ids->create('sales-channel')]);

        /** @var EntityRepository $categoryRepository */
        $categoryRepository = static::getContainer()->get('category.repository');
        $this->categoryRepository = $categoryRepository;

        /** @var EntityRepository $productRepository */
        $productRepository = static::getContainer()->get('product.repository');
        $this->productRepository = $productRepository;
    }

    public function testLoadProducts(): void
    {
        $this->createData();

        $this->browser->request(
            'POST',
            '/store-api/product-listing/' . $this->ids->get('category')
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('product_listing', $response['apiAlias']);
        static::assertCount(6, $response['elements']);
        static::assertSame('product', $response['elements'][0]['apiAlias']);
    }

    public function testLoadProductsUsingDynamicGroupWithEmptyProductStreamId(): void
    {
        $this->createData('product_stream');

        $this->browser->request(
            'POST',
            '/store-api/product-listing/' . $this->ids->get('category')
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());
        static::assertSame('product_listing', $response['apiAlias']);
        static::assertCount(6, $response['elements']);
        static::assertSame('product', $response['elements'][0]['apiAlias']);
    }

    public function testLoadProductsUsingDynamicGroupWithProductStream(): void
    {
        $this->createData('product_stream', $this->ids->create('productStream'));

        $this->browser->request(
            'POST',
            '/store-api/product-listing/' . $this->ids->get('category')
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());
        static::assertSame('product_listing', $response['apiAlias']);
        static::assertCount(1, $response['elements']);
        static::assertSame('product', $response['elements'][0]['apiAlias']);
        static::assertContains($response['elements'][0]['id'], [$this->variantIds['redL'], $this->variantIds['redXl']]);
    }

    public function testLoadProductsUsingDynamicGroupWithProductStreamAndMainVariant(): void
    {
        $this->createData('product_stream', $this->ids->create('productStream'), 'greenL');

        $this->browser->request(
            'POST',
            '/store-api/product-listing/' . $this->ids->get('category')
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());
        static::assertSame('product_listing', $response['apiAlias']);
        static::assertCount(1, $response['elements']);
        static::assertSame('product', $response['elements'][0]['apiAlias']);
        static::assertSame($this->variantIds['greenL'], $response['elements'][0]['id']);
    }

    public function testIncludes(): void
    {
        $this->createData();

        $this->browser->request(
            'POST',
            '/store-api/product-listing/' . $this->ids->get('category'),
            [
                'includes' => [
                    'product_listing' => ['total'],
                ],
            ]
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('product_listing', $response['apiAlias']);
        static::assertArrayNotHasKey('elements', $response);
        static::assertArrayHasKey('total', $response);
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $expected
     */
    #[DataProvider('filterAggregationsWithProducts')]
    public function testFilterAggregationsWithProducts(IdsCollection $ids, array $product, Request $request, array $expected): void
    {
        $parent = static::getContainer()->get(Connection::class)->fetchOne(
            'SELECT LOWER(HEX(navigation_category_id)) FROM sales_channel WHERE id = :id',
            ['id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL)]
        );

        static::getContainer()->get('category.repository')
            ->create([['id' => $ids->get('category'), 'name' => 'test', 'parentId' => $parent]], Context::createDefaultContext());

        $categoryId = $product['categories'][0]['id'];

        static::getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($categoryId, $request, $context, new Criteria())
            ->getResult();

        $aggregation = $listing->getAggregations()->get($expected['aggregation']);

        if ($expected['instanceOf'] === null) {
            static::assertNull($aggregation);
        } else {
            static::assertInstanceOf($expected['instanceOf'], $aggregation);
        }

        if ($expected['aggregation'] === 'properties' && isset($expected['propertyWhitelistIds'])) {
            static::assertInstanceOf(EntityResult::class, $aggregation);
            /** @var PropertyGroupCollection $properties */
            $properties = $aggregation->getEntities();

            static::assertSame($expected['propertyWhitelistIds'], $properties->getIds());
        }
    }

    /**
     * @return list<array{0: IdsCollection, 1: array<string, mixed>, 2: Request, 3: array<string, mixed>}>
     */
    public static function filterAggregationsWithProducts(): array
    {
        $ids = new IdsCollection();

        $defaults = [
            'id' => $ids->get('product'),
            'name' => 'test-product',
            'productNumber' => $ids->get('product'),
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'visibilities' => [
                [
                    'salesChannelId' => TestDefaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
            'categories' => [
                ['id' => $ids->get('category')],
            ],
        ];

        return [
            // property-filter
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request(),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => EntityResult::class,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request([], ['property-filter' => true]),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => EntityResult::class,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request([], ['property-filter' => false]),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => null,
                ],
            ],

            // property-whitelist
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request([], ['property-filter' => false, 'property-whitelist' => null]),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => null,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request([], ['property-filter' => false, 'property-whitelist' => null]),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => null,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request([], ['property-filter' => false, 'property-whitelist' => [$ids->get('textile')]]),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => EntityResult::class,
                    'propertyWhitelistIds' => [$ids->get('textile') => $ids->get('textile')],
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('cotton'),
                            'name' => 'cotton',
                            'groupId' => $ids->get('textile'),
                            'group' => ['id' => $ids->get('textile'), 'name' => 'textile'],
                        ],
                    ],
                ]),
                new Request([], ['property-filter' => false]),
                [
                    'aggregation' => 'properties',
                    'instanceOf' => null,
                ],
            ],

            // manufacturer-filter
            [
                $ids,
                $defaults,
                new Request(),
                [
                    'aggregation' => 'manufacturer',
                    'instanceOf' => EntityResult::class,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'manufacturer' => [
                        'id' => $ids->get('test-manufacturer'),
                        'name' => 'test-manufacturer',
                    ],
                ]),
                new Request([], ['manufacturer-filter' => true]),
                [
                    'aggregation' => 'manufacturer',
                    'instanceOf' => EntityResult::class,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'manufacturer' => [
                        'id' => $ids->get('test-manufacturer'),
                        'name' => 'test-manufacturer',
                    ],
                ]),
                new Request([], ['manufacturer-filter' => false]),
                [
                    'aggregation' => 'manufacturer',
                    'instanceOf' => null,
                ],
            ],

            // price-filter
            [
                $ids,
                $defaults,
                new Request(),
                [
                    'aggregation' => 'price',
                    'instanceOf' => StatsResult::class,
                ],
            ],
            [
                $ids,
                $defaults,
                new Request([], ['manufacturer-filter' => true]),
                [
                    'aggregation' => 'price',
                    'instanceOf' => StatsResult::class,
                ],
            ],
            [
                $ids,
                $defaults,
                new Request([], ['price-filter' => false]),
                [
                    'aggregation' => 'price',
                    'instanceOf' => null,
                ],
            ],

            // rating-filter
            [
                $ids,
                $defaults,
                new Request(),
                [
                    'aggregation' => 'rating',
                    'instanceOf' => MaxResult::class,
                ],
            ],
            [
                $ids,
                $defaults,
                new Request([], ['rating-filter' => true]),
                [
                    'aggregation' => 'rating',
                    'instanceOf' => MaxResult::class,
                ],
            ],
            [
                $ids,
                $defaults,
                new Request([], ['rating-filter' => false]),
                [
                    'aggregation' => 'rating',
                    'instanceOf' => null,
                ],
            ],

            // shipping-free-filter
            [
                $ids,
                array_merge($defaults, [
                    'shippingFree' => false,
                ]),
                new Request(),
                [
                    'aggregation' => 'shipping-free',
                    'instanceOf' => MaxResult::class,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'shippingFree' => true,
                ]),
                new Request([], ['shipping-free-filter' => true]),
                [
                    'aggregation' => 'shipping-free',
                    'instanceOf' => MaxResult::class,
                ],
            ],
            [
                $ids,
                array_merge($defaults, [
                    'shippingFree' => true,
                ]),
                new Request([], ['shipping-free-filter' => false]),
                [
                    'aggregation' => 'shipping-free',
                    'instanceOf' => null,
                ],
            ],
        ];
    }

    private function createData(string $productAssignmentType = 'product', ?string $productStreamId = null, ?string $mainVariant = null): void
    {
        $this->productId = Uuid::randomHex();

        $this->optionIds = [
            'red' => Uuid::randomHex(),
            'green' => Uuid::randomHex(),
            'xl' => Uuid::randomHex(),
            'l' => Uuid::randomHex(),
        ];

        $this->variantIds = [
            'redXl' => Uuid::randomHex(),
            'greenXl' => Uuid::randomHex(),
            'redL' => Uuid::randomHex(),
            'greenL' => Uuid::randomHex(),
        ];

        $this->groupIds = [
            'color' => Uuid::randomHex(),
            'size' => Uuid::randomHex(),
        ];

        $product = [
            'name' => 'test',
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'tax' => ['name' => 'test', 'taxRate' => 15],
            'active' => true,
        ];

        $products = [];
        for ($i = 0; $i < 5; ++$i) {
            $products[] = array_merge(
                [
                    'id' => $this->ids->create('product' . $i),
                    'manufacturer' => ['id' => $this->ids->create('manufacturer-' . $i), 'name' => 'test-' . $i],
                    'productNumber' => $this->ids->get('product' . $i),
                ],
                $product
            );
        }

        $product['id'] = $this->productId;
        $product['configuratorSettings'] = [
            [
                'option' => [
                    'id' => $this->optionIds['red'],
                    'name' => 'Red',
                    'group' => [
                        'id' => $this->groupIds['color'],
                        'name' => 'Color',
                    ],
                ],
            ],
            [
                'option' => [
                    'id' => $this->optionIds['green'],
                    'name' => 'Green',
                    'group' => [
                        'id' => $this->groupIds['color'],
                        'name' => 'Color',
                    ],
                ],
            ],
            [
                'option' => [
                    'id' => $this->optionIds['xl'],
                    'name' => 'XL',
                    'group' => [
                        'id' => $this->groupIds['size'],
                        'name' => 'size',
                    ],
                ],
            ],
            [
                'option' => [
                    'id' => $this->optionIds['l'],
                    'name' => 'L',
                    'group' => [
                        'id' => $this->groupIds['size'],
                        'name' => 'size',
                    ],
                ],
            ],
        ];
        $product['children'] = [
            [
                'id' => $this->variantIds['redXl'],
                'productNumber' => 'a.1',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['red']],
                    ['id' => $this->optionIds['xl']],
                ],
            ],
            [
                'id' => $this->variantIds['greenXl'],
                'productNumber' => 'a.3',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['green']],
                    ['id' => $this->optionIds['xl']],
                ],
            ],
            [
                'id' => $this->variantIds['redL'],
                'productNumber' => 'a.5',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['red']],
                    ['id' => $this->optionIds['l']],
                ],
            ],
            [
                'id' => $this->variantIds['greenL'],
                'productNumber' => 'a.7',
                'stock' => 10,
                'active' => true,
                'parentId' => $this->productId,
                'options' => [
                    ['id' => $this->optionIds['green']],
                    ['id' => $this->optionIds['l']],
                ],
            ],
        ];
        $product['productNumber'] = $this->productId;
        $products[] = $product;

        $data = [
            'id' => $this->ids->create('category'),
            'name' => 'Test',
            'productAssignmentType' => $productAssignmentType,
            'cmsPage' => [
                'id' => $this->ids->create('cms-page'),
                'type' => 'product_list',
                'sections' => [
                    [
                        'position' => 0,
                        'type' => 'sidebar',
                        'blocks' => [
                            [
                                'type' => 'product-listing',
                                'position' => 1,
                                'slots' => [
                                    ['type' => 'product-listing', 'slot' => 'content'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'products' => $products,
        ];

        static::getContainer()->get('product_stream.repository')->create([[
            'id' => $this->ids->create('productStream'),
            'name' => 'test',
            'filters' => [[
                'type' => 'equals',
                'field' => 'options.id',
                'value' => $this->optionIds['red'],
            ]],
        ]], Context::createDefaultContext());

        $this->categoryRepository->upsert([$data], Context::createDefaultContext());
        $this->categoryRepository->upsert([[
            'id' => $this->ids->get('category'),
            'productStreamId' => $productStreamId,
        ]], Context::createDefaultContext());

        if ($mainVariant) {
            $upsertData = [
                [
                    'id' => $this->productId,
                    'variantListingConfig' => [
                        'mainVariantId' => $this->variantIds['greenL'],
                    ],
                ],
            ];
            $this->productRepository->upsert($upsertData, Context::createDefaultContext());
        }

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->get('sales-channel'),
            'navigationCategoryId' => $this->ids->get('category'),
        ]);

        $this->setVisibilities($products);
    }

    /**
     * @param array<int, array<string, array<int|string, array<string, array<int|string, array<string, string>|string>|bool|int|string>|int|string>|bool|int|string>> $createdProducts
     */
    private function setVisibilities(array $createdProducts): void
    {
        $products = [];
        foreach ($createdProducts as $created) {
            $products[] = [
                'id' => $created['id'],
                'visibilities' => [
                    ['salesChannelId' => $this->ids->get('sales-channel'), 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
                ],
            ];
        }

        $this->productRepository->update($products, Context::createDefaultContext());
    }
}
