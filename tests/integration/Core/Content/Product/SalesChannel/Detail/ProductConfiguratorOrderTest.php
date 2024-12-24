<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\Detail;

use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductCollection;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Property\PropertyGroupEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
class ProductConfiguratorOrderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use TaxAddToSalesChannelTestBehaviour;

    /**
     * @var EntityRepository<ProductCollection>
     */
    private EntityRepository $repository;

    /**
     * @var SalesChannelRepository<SalesChannelProductCollection>
     */
    private SalesChannelRepository $salesChannelProductRepository;

    private SalesChannelContext $context;

    private ProductConfiguratorLoader $loader;

    protected function setUp(): void
    {
        $this->repository = static::getContainer()->get('product.repository');

        $this->salesChannelProductRepository = static::getContainer()->get('sales_channel.product.repository');

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create('test', TestDefaults::SALES_CHANNEL);

        $this->loader = static::getContainer()->get(ProductConfiguratorLoader::class);

        parent::setUp();
    }

    public function testDefaultOrder(): void
    {
        $groupNames = $this->getOrder();
        static::assertEquals(['a', 'b', 'c', 'd', 'e', 'f'], $groupNames);
    }

    public function testGroupPositionOrder(): void
    {
        $groupNames = $this->getOrder(['f', 'e', 'd', 'c', 'b', 'a']);
        static::assertEquals(['f', 'e', 'd', 'c', 'b', 'a'], $groupNames);
    }

    public function testConfiguratorGroupConfigOrder(): void
    {
        $groupNames = $this->getOrder(null, ['f', 'e', 'd', 'c', 'b', 'a']);
        static::assertEquals(['f', 'e', 'd', 'c', 'b', 'a'], $groupNames);
    }

    public function testConfiguratorGroupConfigOverrideOrder(): void
    {
        $groupNames = $this->getOrder(['f', 'b', 'c', 'd', 'a', 'e'], ['f', 'e', 'd', 'c', 'b', 'a']);
        static::assertEquals(['f', 'e', 'd', 'c', 'b', 'a'], $groupNames);
    }

    /**
     * @param array<string, string> $a
     */
    private static function ashuffle(array &$a): bool
    {
        $keys = array_keys($a);
        shuffle($keys);
        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $a[$key];
        }
        $a = $shuffled;

        return true;
    }

    /**
     * @param array<string>|null $groupPositionOrder
     * @param array<string>|null $configuratorGroupConfigOrder
     *
     * @return array<int, string|null>
     */
    private function getOrder(?array $groupPositionOrder = null, ?array $configuratorGroupConfigOrder = null): array
    {
        // create product with property groups and 1 variant and get its configurator settings
        $productId = Uuid::randomHex();
        $variantId = Uuid::randomHex();

        $groupIds = [
            'a' => Uuid::randomHex(),
            'b' => Uuid::randomHex(),
            'c' => Uuid::randomHex(),
            'd' => Uuid::randomHex(),
            'e' => Uuid::randomHex(),
            'f' => Uuid::randomHex(),
        ];

        $optionIds = [];

        self::ashuffle($groupIds);

        $configuratorSettings = [];
        foreach ($groupIds as $groupName => $groupId) {
            $group = [
                'id' => $groupId,
                'name' => $groupName,
            ];

            if ($groupPositionOrder) {
                $group['position'] = array_search($groupName, $groupPositionOrder, true);
            }

            // 2 options for each group
            $optionIds[$groupId] = [];
            for ($i = 0; $i < 2; ++$i) {
                $id = Uuid::randomHex();
                $optionIds[$groupId][] = $id;
                $configuratorSettings[] = [
                    'option' => [
                        'id' => $id,
                        'name' => $groupName . $i,
                        'group' => $group,
                    ],
                ];
            }
        }

        $configuratorGroupConfig = null;
        if ($configuratorGroupConfigOrder) {
            $configuratorGroupConfig = [];
            foreach ($configuratorGroupConfigOrder as $groupName) {
                $configuratorGroupConfig[] = [
                    'expressionForListings' => false,
                    'id' => $groupIds[$groupName],
                    'representation' => 'box',
                ];
            }
        }

        $data = [
            [
                'id' => $productId,
                'name' => 'Test product',
                'productNumber' => 'a.0',
                'manufacturer' => ['name' => 'test'],
                'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'test'],
                'stock' => 10,
                'active' => true,
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => true]],
                'configuratorSettings' => $configuratorSettings,
                'variantListingConfig' => [
                    'configuratorGroupConfig' => $configuratorGroupConfig,
                ],
                'visibilities' => [
                    [
                        'salesChannelId' => TestDefaults::SALES_CHANNEL,
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
            [
                'id' => $variantId,
                'productNumber' => 'variant',
                'stock' => 10,
                'active' => true,
                'parentId' => $productId,
                'options' => array_map(fn (array $group) => ['id' => $group[0]], $optionIds),
            ],
        ];

        $this->repository->create($data, Context::createDefaultContext());
        $this->addTaxDataToSalesChannel($this->context, $data[0]['tax']);

        $criteria = (new Criteria())->addFilter(new EqualsFilter('product.parentId', $productId));
        /** @var SalesChannelProductEntity $salesChannelProduct */
        $salesChannelProduct = $this->salesChannelProductRepository->search($criteria, $this->context)->first();

        // get ordered PropertyGroupCollection
        $groups = $this->loader->load($salesChannelProduct, $this->context);
        $propertyGroupNames = array_map(fn (PropertyGroupEntity $propertyGroupEntity) => $propertyGroupEntity->getName(), $groups->getElements());

        return array_values($propertyGroupNames);
    }
}
