<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\SalesChannel\Listing;

use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\Events\ProductListingResolvePreviewEvent;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingLoader;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Struct\ArrayEntity;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\TaxAddToSalesChannelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ProductListingLoader::class)]
#[Group('slow')]
class ProductListingLoaderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;
    use TaxAddToSalesChannelTestBehaviour;

    private EntityRepository $productRepository;

    private ProductListingLoader $productListingLoader;

    private SalesChannelContext $salesChannelContext;

    private SystemConfigService $systemConfigService;

    private ?string $productId = null;

    private ?string $mainVariantId = null;

    /**
     * @var array<string>
     */
    private array $optionIds = [];

    /**
     * @var array<string>
     */
    private array $variantIds = [];

    /**
     * @var array<string>
     */
    private array $groupIds = [];

    protected function setUp(): void
    {
        $this->productRepository = static::getContainer()->get('product.repository');
        $this->productListingLoader = static::getContainer()->get(ProductListingLoader::class);
        $this->salesChannelContext = $this->createSalesChannelContext();
        $this->systemConfigService = static::getContainer()->get(SystemConfigService::class);

        parent::setUp();
    }

    public function testResolvePreviewEvent(): void
    {
        $ids = new IdsCollection();
        $product = (new ProductBuilder($ids, 'p1'))
            ->price(100)
            ->visibility()
            ->build();
        static::getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::once())->method('__invoke');
        static::getContainer()->get('event_dispatcher')->addListener(ProductListingResolvePreviewEvent::class, $listener);
        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $criteria = new Criteria($ids->getList(['p1']));
        $this->productListingLoader->load($criteria, $context);
    }

    public function testMainVariant(): void
    {
        $this->createProduct([], true);

        $listing = $this->fetchListing();

        static::assertEquals(1, $listing->getTotal());

        $mainVariant = $listing->getEntities()->first();
        static::assertNotNull($mainVariant);

        static::assertEquals($this->mainVariantId, $mainVariant->getId());
        static::assertContains($this->optionIds['red'], $mainVariant->getOptionIds() ?: []);
        static::assertContains($this->optionIds['l'], $mainVariant->getOptionIds() ?: []);
        static::assertTrue($mainVariant->hasExtension('search'));

        static::assertTrue($listing->getCriteria()->hasState(Criteria::STATE_ELASTICSEARCH_AWARE));
    }

    public function testMainVariantInactive(): void
    {
        $this->createProduct([], true);

        // update main variant to be inactive
        $this->productRepository->update([[
            'id' => $this->mainVariantId,
            'active' => false,
        ]], $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();

        // another random variant of the product should be displayed
        static::assertEquals(1, $listing->getTotal());

        $firstVariant = $listing->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        static::assertNotEquals($this->mainVariantId, $variantId);
        static::assertContains($variantId, $this->variantIds);
        static::assertTrue($firstVariant->hasExtension('search'));
    }

    public function testMainVariantRemoved(): void
    {
        $this->createProduct([], true);

        $this->productRepository->delete([['id' => $this->mainVariantId]], $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();

        // another random variant of the product should be displayed
        static::assertEquals(1, $listing->getTotal());

        $firstVariant = $listing->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        static::assertNotEquals($this->mainVariantId, $variantId);
        static::assertContains($variantId, $this->variantIds);
        static::assertTrue($firstVariant->hasExtension('search'));
    }

    public function testMainVariantOutOfStock(): void
    {
        $this->createProduct([], true);

        $this->systemConfigService->set(
            'core.listing.hideCloseoutProductsWhenOutOfStock',
            true,
            $this->salesChannelContext->getSalesChannelId()
        );

        // update main variant to be out of stock
        $this->productRepository->update([[
            'id' => $this->mainVariantId,
            'stock' => 0,
            'isCloseout' => true,
        ]], $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();

        // another random variant of the product should be displayed
        static::assertEquals(1, $listing->getTotal());

        $firstVariant = $listing->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        static::assertNotEquals($this->mainVariantId, $variantId);
        static::assertContains($variantId, $this->variantIds);
        static::assertTrue($firstVariant->hasExtension('search'));
    }

    public function testChangeProductConfigToSingleVariant(): void
    {
        // no main variant will be set initially
        $this->createProduct(['color', 'size'], false);

        // update product with a main variant
        $this->productRepository->update([[
            'id' => $this->productId,
            'variantListingConfig' => [
                'displayParent' => false,
                'mainVariantId' => $this->mainVariantId,
                'configuratorGroupConfig' => [],
            ],
        ]], $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();

        static::assertEquals(1, $listing->getTotal());

        // only main variant should be returned
        $mainVariant = $listing->getEntities()->first();
        static::assertNotNull($mainVariant);

        $optionIds = $mainVariant->getOptionIds();
        static::assertNotNull($optionIds);
        static::assertEquals($this->mainVariantId, $mainVariant->getId());
        static::assertContains($this->optionIds['red'], $optionIds);
        static::assertContains($this->optionIds['l'], $optionIds);
        static::assertTrue($mainVariant->hasExtension('search'));
    }

    public function testChangeProductConfigToMainProduct(): void
    {
        // no main variant will be set initially
        $this->createProduct(['color', 'size'], false);

        // update product with a main variant
        $this->productRepository->update([
            [
                'id' => $this->productId,
                'variantListingConfig' => [
                    'displayParent' => true,
                    'mainVariantId' => $this->mainVariantId,
                    'configuratorGroupConfig' => [],
                ],
            ],
        ], $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();

        static::assertEquals(1, $listing->getTotal());

        // only main product should be returned
        $mainProduct = $listing->getEntities()->first();
        static::assertNotNull($mainProduct);

        static::assertEquals($this->productId, $mainProduct->getId());
        static::assertEquals($this->mainVariantId, $mainProduct->getVariantListingConfig()?->getMainVariantId());
        static::assertTrue($mainProduct->hasExtension('search'));
    }

    public function testMainProductIsHiddenIfVariantsOutOfStock(): void
    {
        $this->createProduct([]);

        $this->systemConfigService->set(
            'core.listing.hideCloseoutProductsWhenOutOfStock',
            true,
            $this->salesChannelContext->getSalesChannelId()
        );

        $this->productRepository->update([[
            'id' => $this->productId,
            'displayParent' => true,
            'mainVariantId' => $this->mainVariantId,
            'configuratorGroupConfig' => [],
            'isCloseout' => true,
        ]], $this->salesChannelContext->getContext());

        $variants = array_values(\array_map(fn ($item) => ['id' => $item, 'stock' => 0], $this->variantIds));

        $this->productRepository->update($variants, $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();
        static::assertEquals(0, $listing->getTotal());
    }

    public function testMainProductIsHiddenIfAllVariantsDisabled(): void
    {
        $this->createProduct([]);

        $this->productRepository->update([[
            'id' => $this->productId,
            'displayParent' => true,
            'mainVariantId' => $this->mainVariantId,
            'configuratorGroupConfig' => [],
        ]], $this->salesChannelContext->getContext());

        $variants = array_values(\array_map(fn ($item) => ['id' => $item, 'active' => false], $this->variantIds));

        $this->productRepository->update($variants, $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();
        static::assertEquals(0, $listing->getTotal());
    }

    public function testNoListConfig(): void
    {
        $this->createProduct([]);

        $this->productRepository->update([[
            'id' => $this->productId,
            'displayParent' => null,
            'mainVariantId' => null,
            'configuratorGroupConfig' => null,
        ]], $this->salesChannelContext->getContext());

        $firstVariant = $this->fetchListing()->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        static::assertContains($variantId, $this->variantIds);
    }

    public function testChangeProductConfigToVariantGroups(): void
    {
        // main variant will be set initially
        $this->createProduct([], true);

        // update product with no main variant
        $this->productRepository->update([[
            'id' => $this->productId,
            'variantListingConfig' => [
                'mainVariantId' => null,
                'configuratorGroupConfig' => $this->getListingConfiguration(['color', 'size']),
            ],
        ]], $this->salesChannelContext->getContext());

        $listing = $this->fetchListing();

        // all variants should be returned
        static::assertEquals(4, $listing->getTotal());

        $variants = $listing->getIds();

        static::assertContains($this->variantIds['redXl'], $variants);
        static::assertContains($this->variantIds['redL'], $variants);
        static::assertContains($this->variantIds['greenL'], $variants);
        static::assertContains($this->variantIds['greenXl'], $variants);

        foreach ($listing as $variant) {
            static::assertInstanceOf(ProductEntity::class, $variant);
            static::assertTrue($variant->hasExtension('search'));
        }
    }

    public function testMainVariantAndVariantGroups(): void
    {
        // main variant and variant groups be set initially
        $this->createProduct(['color', 'size'], true);

        $listing = $this->fetchListing();

        // only the main variant should be returned
        static::assertEquals(1, $listing->getTotal());

        $firstVariant = $listing->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        static::assertEquals($this->mainVariantId, $variantId);
        static::assertTrue($firstVariant->hasExtension('search'));
    }

    public function testMainVariantAndVariantGroupsWithFilterOnOptions(): void
    {
        // main variant and variant groups be set initially
        $this->createProduct(['color', 'size'], true);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.options.id', $this->optionIds['green']));
        $listing = $this->fetchListing($criteria);

        // only the main variant should be returned
        static::assertEquals(1, $listing->getTotal());

        $firstVariant = $listing->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        static::assertEquals($this->mainVariantId, $variantId);
        static::assertTrue($firstVariant->hasExtension('search'));
    }

    public function testMainVariantAndVariantGroupsWithPostFilterOnOptions(): void
    {
        // main variant and variant groups be set initially
        $this->createProduct(['color', 'size'], true);

        $criteria = new Criteria();
        $criteria->addPostFilter(new EqualsFilter('product.options.id', $this->optionIds['green']));
        $listing = $this->fetchListing($criteria);

        // only one of the green variants should be returned
        static::assertEquals(1, $listing->getTotal());

        $firstVariant = $listing->getEntities()->first();
        static::assertNotNull($firstVariant);
        $variantId = $firstVariant->getId();

        $expectedVariants = [$this->variantIds['greenL'], $this->variantIds['greenXl']];
        static::assertContains($variantId, $expectedVariants);
        static::assertTrue($firstVariant->hasExtension('search'));
    }

    public function testAllVariants(): void
    {
        $this->createProduct(['size', 'color'], false);

        $listing = $this->fetchListing();

        // all variants should be returned
        static::assertEquals(4, $listing->getTotal());

        $variants = $listing->getIds();

        static::assertContains($this->variantIds['redXl'], $variants);
        static::assertContains($this->variantIds['redL'], $variants);
        static::assertContains($this->variantIds['greenL'], $variants);
        static::assertContains($this->variantIds['greenXl'], $variants);

        foreach ($listing as $variant) {
            static::assertTrue($variant->hasExtension('search'));
        }
    }

    public function testMainVariantHasScoreInSearchExtension(): void
    {
        $this->createProduct([], true);

        $listing = $this->fetchListing();

        static::assertEquals(1, $listing->getTotal());

        $mainVariant = $listing->getEntities()->first();
        static::assertNotNull($mainVariant);

        static::assertEquals($this->mainVariantId, $mainVariant->getId());
        static::assertContains($this->optionIds['red'], $mainVariant->getOptionIds() ?: []);
        static::assertContains($this->optionIds['l'], $mainVariant->getOptionIds() ?: []);
        static::assertTrue($mainVariant->hasExtension('search'));

        $searchData = $mainVariant->get('search');
        static::assertInstanceOf(ArrayEntity::class, $searchData);
        static::assertTrue($searchData->get('_score') > 0);
    }

    /**
     * @return EntitySearchResult<ProductCollection>
     */
    private function fetchListing(?Criteria $criteria = null): EntitySearchResult
    {
        if (!$criteria instanceof Criteria) {
            $criteria = new Criteria();
        }

        $criteria->addFilter(new EqualsFilter('product.parentId', $this->productId));
        $criteria->setTerm('example');

        return $this->productListingLoader->load($criteria, $this->salesChannelContext);
    }

    /**
     * @param array<string> $listingProperties
     */
    private function createProduct(array $listingProperties, bool $hasMainVariant = false): void
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

        $this->variantIds['mainVariantId'] = $this->variantIds['redL'];

        $this->groupIds = [
            'color' => Uuid::randomHex(),
            'size' => Uuid::randomHex(),
        ];

        $this->mainVariantId = $this->variantIds['redL'];

        $config = $this->getListingConfiguration($listingProperties);

        $tax = ['id' => Uuid::randomHex(), 'name' => '19', 'taxRate' => 19];

        $data = [
            [
                'id' => $this->productId,
                'variantListingConfig' => [
                    'displayParent' => null,
                    'mainVariantId' => null,
                    'configuratorGroupConfig' => $config,
                ],
                'productNumber' => 'a.0',
                'manufacturer' => ['name' => 'test'],
                'tax' => $tax,
                'stock' => 10,
                'name' => 'example',
                'active' => true,
                'price' => [
                    ['currencyId' => Defaults::CURRENCY, 'gross' => 10, 'net' => 9, 'linked' => true],
                ],
                'configuratorSettings' => [
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
                ],
                'visibilities' => [
                    [
                        'salesChannelId' => $this->salesChannelContext->getSalesChannelId(),
                        'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                    ],
                ],
            ],
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

        $this->addTaxDataToSalesChannel($this->salesChannelContext, $tax);

        $this->productRepository->create($data, $this->salesChannelContext->getContext());

        if ($hasMainVariant) {
            // Update parent product, configure to use selected mainVariantId in listing config
            $this->productRepository->update([
                [
                    'id' => $this->productId,
                    'variantListingConfig' => [
                        'displayParent' => null,
                        'mainVariantId' => $this->mainVariantId,
                        'configuratorGroupConfig' => $config,
                    ],
                ],
            ], $this->salesChannelContext->getContext());
        }
    }

    /**
     * @param array<string> $listingProperties
     *
     * @return array<int, array<string, string|true>>
     */
    private function getListingConfiguration(array $listingProperties): array
    {
        $config = [];

        foreach ($listingProperties as $groupName) {
            $config[] = [
                'id' => $this->groupIds[$groupName],
                'expressionForListings' => true,
                'representation' => 'box', // box, select, image, color
            ];
        }

        return $config;
    }
}
