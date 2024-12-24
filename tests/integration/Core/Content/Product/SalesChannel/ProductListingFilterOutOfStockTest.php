<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingRoute;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Content\Test\Product\SalesChannel\Fixture\ListingTestData;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\EntityResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ProductListingFilterOutOfStockTest extends TestCase
{
    use IntegrationTestBehaviour;

    private string $categoryId;

    private ListingTestData $testData;

    protected function setUp(): void
    {
        parent::setUp();

        $parent = static::getContainer()->get(Connection::class)->fetchOne(
            'SELECT LOWER(HEX(navigation_category_id)) FROM sales_channel WHERE id = :id',
            ['id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL)]
        );

        $this->categoryId = Uuid::randomHex();

        static::getContainer()->get('category.repository')
            ->create([['id' => $this->categoryId, 'name' => 'test', 'parentId' => $parent]], Context::createDefaultContext());

        $this->testData = new ListingTestData();

        $this->insertOptions();

        $this->insertProducts();
    }

    public function testListingWithFilterDisabled(): void
    {
        // disable hideCloseoutProductsWhenOutOfStock filter
        static::getContainer()->get(SystemConfigService::class)
            ->set('core.listing.hideCloseoutProductsWhenOutOfStock', false);

        $request = new Request();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($this->categoryId, $request, $context, new Criteria())
            ->getResult();

        static::assertSame(5, $listing->getTotal());
        static::assertFalse($listing->has($this->testData->getId('product1')));
        static::assertFalse($listing->has($this->testData->getId('product2')));

        // product 1 has all available variants
        static::assertTrue($listing->has($this->testData->getId('product1-red')));
        static::assertTrue($listing->has($this->testData->getId('product1-green')));
        static::assertTrue($listing->has($this->testData->getId('product1-blue')));

        // product 2 has all available variants
        static::assertTrue($listing->has($this->testData->getId('product2-green')));
        static::assertTrue($listing->has($this->testData->getId('product2-red')));

        /** @var EntityResult<PropertyGroupCollection> $result */
        $result = $listing->getAggregations()->get('properties');
        $options = $result->getEntities();

        $ids = array_keys($options->getOptionIdMap());

        static::assertContains($this->testData->getId('green'), $ids);
        static::assertContains($this->testData->getId('red'), $ids);
        static::assertContains($this->testData->getId('blue'), $ids);
    }

    public function testListingWithFilterEnabled(): void
    {
        // enable hideCloseoutProductsWhenOutOfStock filter
        static::getContainer()->get(SystemConfigService::class)
            ->set('core.listing.hideCloseoutProductsWhenOutOfStock', true);

        $request = new Request();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $listing = static::getContainer()
            ->get(ProductListingRoute::class)
            ->load($this->categoryId, $request, $context, new Criteria())
            ->getResult();

        static::assertSame(2, $listing->getTotal());
        static::assertFalse($listing->has($this->testData->getId('product1')));
        static::assertFalse($listing->has($this->testData->getId('product2')));

        // product 1 has only 2 available variants
        static::assertTrue($listing->has($this->testData->getId('product1-red')));
        static::assertTrue($listing->has($this->testData->getId('product1-green')));
        static::assertFalse($listing->has($this->testData->getId('product1-blue')));

        // product 2 has no available variants
        static::assertFalse($listing->has($this->testData->getId('product2-green')));
        static::assertFalse($listing->has($this->testData->getId('product2-red')));

        /** @var EntityResult<PropertyGroupCollection> $result */
        $result = $listing->getAggregations()->get('properties');
        $options = $result->getEntities();

        $ids = array_keys($options->getOptionIdMap());

        static::assertContains($this->testData->getId('green'), $ids);
        static::assertContains($this->testData->getId('red'), $ids);
        static::assertNotContains($this->testData->getId('blue'), $ids);
    }

    private function insertProducts(): void
    {
        $this->createProduct(
            'product1',
            [
                [
                    'combination' => [$this->testData->getId('red')],
                    'stock' => 1,
                ],
                [
                    'combination' => [$this->testData->getId('blue')],
                    'stock' => 0,
                ],
                [
                    'combination' => [$this->testData->getId('green')],
                    'stock' => 1,
                ],
            ],
            [$this->testData->getId('color')]
        );

        $this->createProduct(
            'product2',
            [
                [
                    'combination' => [$this->testData->getId('red')],
                    'stock' => 0,
                ],
                [
                    'combination' => [$this->testData->getId('green')],
                    'stock' => 0,
                ],
            ],
            [$this->testData->getId('color')]
        );
    }

    /**
     * @param array<array{combination: array<string>, stock: int}> $options
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
            foreach ($grouped['combination'] as $optionId) {
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
                'stock' => 0,
                'isCloseout' => true,
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
                        'salesChannelId' => TestDefaults::SALES_CHANNEL,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
        ];

        if (!empty($options)) {
            foreach ($options as $index => $option) {
                $combination = $option['combination'];

                $variantKey = $key . '-' . implode('-', $this->testData->getKeyList($combination));

                $data[] = [
                    'id' => $this->testData->createId($variantKey),
                    'productNumber' => $key . '.' . $index,
                    'stock' => $option['stock'],
                    'isCloseout' => true,
                    'name' => $variantKey,
                    'active' => true,
                    'parentId' => $this->testData->getId($key),
                    'options' => array_map(static fn ($id) => ['id' => $id], $combination),
                ];
            }
        }

        static::getContainer()->get('product.repository')->create($data, Context::createDefaultContext());
    }

    private function insertOptions(): void
    {
        static::getContainer()->get('property_group.repository')->create([
            [
                'id' => $this->testData->createId('color'),
                'name' => 'color',
                'options' => [
                    ['id' => $this->testData->createId('green'), 'name' => 'green'],
                    ['id' => $this->testData->createId('red'), 'name' => 'red'],
                    ['id' => $this->testData->createId('blue'), 'name' => 'blue'],
                ],
            ],
        ], Context::createDefaultContext());
    }
}
