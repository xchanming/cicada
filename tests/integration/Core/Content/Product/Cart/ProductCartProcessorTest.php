<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Cicada\Core\Checkout\Cart\LineItem\CartDataCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItemFactoryHandler\ProductLineItemFactory;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Content\Product\Aggregate\ProductFeatureSet\ProductFeatureSetDefinition;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\Cart\ProductCartProcessor;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\CustomField\CustomFieldTypes;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProductCartProcessorTest extends TestCase
{
    use IntegrationTestBehaviour;

    final public const TEST_LANGUAGE_LOCALE_CODE = 'sw-AG';
    final public const TEST_LANGUAGE_ID = 'cc72c24b82684d72a4ce91054da264bf';
    final public const TEST_LOCALE_ID = 'cf735c44dc7b4428bb3870fe4ffea2df';
    final public const CUSTOM_FIELD_ID = '24c8b3e8cacc4bf2a743b8c5a7522a33';
    final public const PURCHASE_STEP_QUANTITY_ERROR_KEY = 'purchase-steps-quantity';
    final public const MIN_ORDER_QUANTITY_ERROR_KEY = 'min-order-quantity';
    final public const PRODUCT_STOCK_REACHED_ERROR_KEY = 'product-stock-reached';

    private IdsCollection $ids;

    private CartService $cartService;

    private QuantityPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ids = new IdsCollection();
        $this->cartService = static::getContainer()->get(CartService::class);
        $this->calculator = static::getContainer()->get(QuantityPriceCalculator::class);
    }

    public function testDeliveryInformation(): void
    {
        $this->createProduct();

        $cart = $this->getProductCart();
        $lineItem = $cart->get($this->ids->get('product'));

        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertInstanceOf(DeliveryInformation::class, $lineItem->getDeliveryInformation());

        $info = $lineItem->getDeliveryInformation();
        static::assertEquals(100, $info->getWeight());
        static::assertEquals(101, $info->getHeight());
        static::assertEquals(102, $info->getWidth());
        static::assertEquals(103, $info->getLength());
    }

    public function testDeliveryInformationWithEmptyWeight(): void
    {
        $this->createProduct(['weight' => null]);

        $cart = $this->getProductCart();
        $lineItem = $cart->get($this->ids->get('product'));

        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertInstanceOf(DeliveryInformation::class, $lineItem->getDeliveryInformation());

        $info = $lineItem->getDeliveryInformation();

        static::assertNull($info->getWeight());
    }

    public function testNotCompletedLogic(): void
    {
        $context = $this->getContext();

        $this->createProduct();
        $cart = $this->getProductCart();

        $lineItem = $cart->get($this->ids->get('product'));
        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertSame('test', $lineItem->getLabel());

        $update = ['id' => $this->ids->get('product'), 'name' => 'update'];
        static::getContainer()->get('product.repository')->upsert([$update], $context->getContext());

        $cart = $this->cartService->getCart($context->getToken(), $this->getContext(), false);

        $lineItem = $cart->get($this->ids->get('product'));
        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertSame('update', $lineItem->getLabel());
    }

    public function testReferencePriceWithZeroPurchaseUnit(): void
    {
        $this->createProduct([
            'purchaseUnit' => 0.0,
            'referenceUnit' => 1.0,
            'unit' => [
                'shortCode' => 't',
                'name' => 'test',
            ],
        ]);

        $cart = $this->getProductCart();
        $lineItem = $cart->get($this->ids->get('product'));

        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertInstanceOf(QuantityPriceDefinition::class, $lineItem->getPriceDefinition());

        /** @var QuantityPriceDefinition $priceDefinition */
        $priceDefinition = $lineItem->getPriceDefinition();
        static::assertNull($priceDefinition->getReferencePriceDefinition());

        static::assertInstanceOf(CalculatedPrice::class, $lineItem->getPrice());
        static::assertNull($lineItem->getPrice()->getReferencePrice());
    }

    #[DataProvider('advancedPricingProvider')]
    public function testAdvancedPricing(bool $valid, float $price): void
    {
        $ids = new IdsCollection();

        $product = (new ProductBuilder($ids, 'test'))
            ->price(100)
            ->prices('rule-1', 200, 'default', null, 1, $valid)
            ->visibility()
            ->build();

        static::getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $result = static::getContainer()->get(CartRuleLoader::class)
            ->loadByToken($context, Uuid::randomHex());

        $cart = $result->getCart();

        static::assertEquals($valid, \in_array($ids->get('rule-1'), $context->getRuleIds(), true));

        $lineItem = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $ids->get('test'), 'referencedId' => $ids->get('test')], $context);

        $cart = static::getContainer()->get(CartService::class)
            ->add($cart, [$lineItem], $context);

        static::assertCount(1, $cart->getLineItems());

        $lineItem = $cart->getLineItems()->first();
        static::assertNotNull($lineItem);
        static::assertEquals('product', $lineItem->getType());
        static::assertEquals($ids->get('test'), $lineItem->getReferencedId());

        /** @var CalculatedPrice $calcPrice */
        $calcPrice = $lineItem->getPrice();
        static::assertEquals($price, $calcPrice->getTotalPrice());
    }

    /**
     * @return \Traversable<string, array{0: bool, 1: int}>
     */
    public static function advancedPricingProvider(): \Traversable
    {
        yield 'Test not matching rule' => [false, 100];

        yield 'Test matching rule' => [true, 200];
    }

    public function testOverwriteLabelNoPermission(): void
    {
        $this->createProduct();
        $service = static::getContainer()->get(CartService::class);
        $token = $this->ids->create('token');
        $context = static::getContainer()->get(SalesChannelContextService::class)
            ->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $token));

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);

        $product->setLabel('My special product');

        $cart = $service->getCart($token, $context);
        $service->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertSame('test', $actualProduct->getLabel());
    }

    public function testOverwriteLabelWithPermission(): void
    {
        $this->createProduct();
        $service = static::getContainer()->get(CartService::class);
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [ProductCartProcessor::ALLOW_PRODUCT_LABEL_OVERWRITES => true],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);

        $product->setLabel('My special product');

        $cart = $service->getCart($token, $context);
        $service->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertSame('My special product', $actualProduct->getLabel());
    }

    public function testOverwriteLabelWithPermissionNoLabel(): void
    {
        $this->createProduct();
        $service = static::getContainer()->get(CartService::class);
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [ProductCartProcessor::ALLOW_PRODUCT_LABEL_OVERWRITES => true],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);

        $product->setLabel(null);

        $cart = $service->getCart($token, $context);
        $service->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertSame('test', $actualProduct->getLabel());
    }

    #[Group('slow')]
    public function testLineItemPropertiesPurchasePrice(): void
    {
        $this->createProduct();

        $token = $this->ids->create('token');
        $salesChannelContextService = static::getContainer()->get(SalesChannelContextService::class);
        $context = $salesChannelContextService->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $token, null, Defaults::CURRENCY));
        $cartService = static::getContainer()->get(CartService::class);
        $cart = $cartService->getCart($token, $context);
        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);
        $cartService->add($cart, $product, $context);

        $productCartProcessor = static::getContainer()->get(ProductCartProcessor::class);
        $productCartProcessor->collect(
            new CartDataCollection(),
            $cart,
            $context,
            new CartBehavior()
        );

        $lineItem = $cart->get($product->getId());

        static::assertInstanceOf(LineItem::class, $lineItem);
        $payload = $lineItem->getPayload();
        $purchasePrices = json_decode((string) $payload['purchasePrices'], true, 512, \JSON_THROW_ON_ERROR);
        static::assertSame(Defaults::CURRENCY, $purchasePrices['currencyId']);
        static::assertSame(7.5, $purchasePrices['gross']);
        static::assertSame(5, $purchasePrices['net']);
        static::assertFalse($purchasePrices['linked']);
    }

    public function testPayloadContainsFeatures(): void
    {
        $this->createProduct();

        $cart = $this->getProductCart();
        $lineItem = $cart->get($this->ids->get('product'));

        static::assertInstanceOf(LineItem::class, $lineItem);
        static::assertArrayHasKey('features', $lineItem->getPayload());
    }

    /**
     * @param array{type: string} $testedFeature
     * @param array<string, mixed> $productData
     * @param array{type: string, value: array{price: string}, label: string} $expectedFeature
     */
    #[DataProvider('productFeatureProdiver')]
    #[Group('slow')]
    public function testProductFeaturesContainCorrectInformation(array $testedFeature, array $productData, array $expectedFeature): void
    {
        $this->createLanguage(self::TEST_LANGUAGE_ID);

        if ($testedFeature['type'] === ProductFeatureSetDefinition::TYPE_PRODUCT_CUSTOM_FIELD) {
            $this->createCustomField([]);
        }

        $this->createProduct([...[
            'featureSet' => $this->createFeatureSet([$testedFeature]),
        ], ...$productData]);

        $cart = $this->getProductCart();
        $lineItem = $cart->get($this->ids->get('product'));

        static::assertInstanceOf(LineItem::class, $lineItem);
        $features = $lineItem->getPayload()['features'];
        $feature = array_pop($features);

        static::assertArrayHasKey('label', $feature);
        static::assertArrayHasKey('value', $feature);
        static::assertArrayHasKey('type', $feature);

        if ($testedFeature['type'] === ProductFeatureSetDefinition::TYPE_PRODUCT_REFERENCE_PRICE) {
            unset($expectedFeature['value']['price'], $feature['value']['price']);
        }

        static::assertEquals($expectedFeature, $feature);
    }

    /**
     * @return array{
     *     0: array{type: string},
     *     1: array<string, mixed>,
     *     2: array{type: string, value: mixed, label: string}
     *     }[]
     */
    public static function productFeatureProdiver(): array
    {
        return [
            [
                [
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_ATTRIBUTE,
                    'id' => null,
                    'name' => 'description',
                    'position' => 1,
                ],
                [
                    'translations' => [
                        Defaults::LANGUAGE_SYSTEM => [
                            'name' => 'Default',
                            'description' => 'Default',
                        ],
                        self::TEST_LANGUAGE_ID => [
                            'description' => 'Lorem ipsum dolor sit amet.',
                        ],
                    ],
                ],
                [
                    'label' => 'description',
                    'value' => 'Lorem ipsum dolor sit amet.',
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_ATTRIBUTE,
                ],
            ],
            [
                [
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_ATTRIBUTE,
                    'id' => null,
                    'name' => 'manufacturerNumber',
                    'position' => 1,
                ],
                [
                    'manufacturerNumber' => '22ee3d8063da',
                ],
                [
                    'label' => 'manufacturerNumber',
                    'value' => '22ee3d8063da',
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_ATTRIBUTE,
                ],
            ],
            [
                [
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_PROPERTY,
                    'id' => '7c8e7851ff88447ba254d3c2a7c45101',
                    'name' => null,
                    'position' => 2,
                ],
                [
                    'properties' => [
                        [
                            'id' => 'bf821e9e206848579049bc1694c5c3e7',
                        ],
                        [
                            'id' => '0cfabe6eab0440b0974b7b7164556612',
                        ],
                    ],
                    'options' => [
                        [
                            'id' => 'bf821e9e206848579049bc1694c5c3e7',
                            'position' => 99,
                            'colorHexCode' => '#189eff',
                            'group' => [
                                'id' => '7c8e7851ff88447ba254d3c2a7c45101',
                                'position' => 1,
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => [
                                        'name' => 'Default',
                                        'description' => 'Default',
                                        'displayType' => 'Default',
                                        'sortingType' => 'Default',
                                    ],
                                    self::TEST_LANGUAGE_ID => [
                                        'name' => 'swag_color',
                                        'description' => 'Lorem ipsum',
                                        'displayType' => 'color',
                                        'sortingType' => 'alphanumeric',
                                    ],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => [
                                    'name' => 'Default',
                                ],
                                self::TEST_LANGUAGE_ID => [
                                    'name' => 'Blue',
                                ],
                            ],
                        ],
                        [
                            'id' => '0cfabe6eab0440b0974b7b7164556612',
                            'position' => 98,
                            'colorHexCode' => '#ff0000',
                            'groupId' => '7c8e7851ff88447ba254d3c2a7c45101',
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => [
                                    'name' => 'Default',
                                ],
                                self::TEST_LANGUAGE_ID => [
                                    'name' => 'Red',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'swag_color',
                    'value' => [
                        '0cfabe6eab0440b0974b7b7164556612' => [
                            'id' => '0cfabe6eab0440b0974b7b7164556612',
                            'name' => 'Red',
                            'mediaId' => null,
                            'colorHexCode' => '#ff0000',
                        ],
                        'bf821e9e206848579049bc1694c5c3e7' => [
                            'id' => 'bf821e9e206848579049bc1694c5c3e7',
                            'name' => 'Blue',
                            'mediaId' => null,
                            'colorHexCode' => '#189eff',
                        ],
                    ],
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_PROPERTY,
                ],
            ],
            [
                [
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_CUSTOM_FIELD,
                    'id' => null,
                    'name' => 'lorem_ipsum',
                    'position' => 3,
                ],
                [
                    'translations' => [
                        Defaults::LANGUAGE_SYSTEM => [
                            'name' => 'Default',
                            'customFields' => [
                                'lorem_ipsum' => 'Default',
                            ],
                        ],
                        self::TEST_LANGUAGE_ID => [
                            'customFields' => [
                                'lorem_ipsum' => 'Dolor sit amet.',
                            ],
                        ],
                    ],
                ],
                [
                    'label' => 'lorem_ipsum',
                    'value' => [
                        'id' => self::CUSTOM_FIELD_ID,
                        'type' => CustomFieldTypes::TEXT,
                        'content' => 'Dolor sit amet.',
                    ],
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_CUSTOM_FIELD,
                ],
            ],
            [
                [
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_REFERENCE_PRICE,
                    'id' => null,
                    'name' => null,
                    'position' => 0,
                ],
                [
                    'translations' => [
                        Defaults::LANGUAGE_SYSTEM => [
                            'name' => 'Default',
                            'packUnit' => 'Default',
                            'packUnitPlural' => 'Default',
                        ],
                        self::TEST_LANGUAGE_ID => [
                            'packUnit' => 'Can',
                            'packUnitPlural' => 'Cans',
                        ],
                    ],
                    'unit' => [
                        'translations' => [
                            Defaults::LANGUAGE_SYSTEM => [
                                'shortCode' => 'Default',
                                'name' => 'Default',
                            ],
                            self::TEST_LANGUAGE_ID => [
                                'shortCode' => 'l',
                                'name' => 'litres',
                            ],
                        ],
                    ],
                    'purchaseUnit' => 2,
                    'referenceUnit' => 0.33,
                ],
                [
                    'label' => ProductFeatureSetDefinition::TYPE_PRODUCT_REFERENCE_PRICE,
                    'value' => [
                        'purchaseUnit' => 2.0,
                        'referenceUnit' => 0.33,
                        'unitName' => 'litres',
                    ],
                    'type' => ProductFeatureSetDefinition::TYPE_PRODUCT_REFERENCE_PRICE,
                ],
            ],
        ];
    }

    public function testProcessCartShouldSkipProductStockValidation(): void
    {
        $this->createProduct();
        $service = static::getContainer()->get(CartService::class);
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION => true],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);

        $product->setLabel('My special product');

        $cart = $service->getCart($token, $context);
        $service->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());

        static::assertInstanceOf(LineItem::class, $product);
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertNotNull($product->getPriceDefinition());
        static::assertInstanceOf(QuantityPriceDefinition::class, $product->getPriceDefinition());
        static::assertEquals($product->getQuantity(), $actualProduct->getQuantity());
        static::assertEquals($product->getPrice(), $this->calculator->calculate($product->getPriceDefinition(), $context));
        static::assertEquals($product, $actualProduct);
    }

    #[DataProvider('productDeliverabilityProvider')]
    #[Group('slow')]
    public function testProcessCartShouldReturnFixedQuantity(int $minPurchase, int $purchaseSteps, int $maxPurchase, int $quantity, int $quantityExpected, ?string $errorKey): void
    {
        $additionalData = [
            'maxPurchase' => $maxPurchase,
            'minPurchase' => $minPurchase,
            'purchaseSteps' => $purchaseSteps,
        ];
        $this->createProduct($additionalData);
        $service = static::getContainer()->get(CartService::class);
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $config = [
            'id' => $this->ids->get('product'),
            'referencedId' => $this->ids->get('product'),
            'quantity' => $quantity,
        ];
        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create($config, $context);

        $product->setLabel('My special product');

        $cart = $service->getCart($token, $context);
        $service->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertEquals($quantityExpected, $actualProduct->getQuantity());
        if ($errorKey !== null) {
            $error = $service->getCart($token, $context)->getErrors()->first();
            static::assertNotNull($error);
            static::assertEquals($errorKey, $error->getMessageKey());
        }
    }

    /**
     * @return array<string, array{0: int, 1: int, 2: int, 3: int, 4: int, 5: string}>
     */
    public static function productDeliverabilityProvider(): array
    {
        return [
            'fixed quantity should be return 2' => [2, 2, 20, 3, 2, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 4' => [2, 2, 20, 5, 4, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 3' => [1, 2, 20, 4, 3, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 9' => [1, 2, 20, 10, 9, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 5, actual quantity is 6' => [5, 5, 20, 6, 5, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 5, actual quantity is 7' => [5, 5, 20, 7, 5, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 5, actual quantity is 8' => [5, 5, 20, 8, 5, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 5, actual quantity is 9' => [5, 5, 20, 9, 5, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return equal max purchase' => [2, 2, 20, 22, 20, self::PRODUCT_STOCK_REACHED_ERROR_KEY],
            'fixed quantity should be return equal min purchase' => [2, 2, 20, 1, 2, self::MIN_ORDER_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 1' => [1, 3, 5, 2, 1, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 10 with purchase step error message' => [10, 3, 13, 11, 10, self::PURCHASE_STEP_QUANTITY_ERROR_KEY],
            'fixed quantity should be return 10, with min order quantity error message' => [10, 2, 20, 2, 10, self::MIN_ORDER_QUANTITY_ERROR_KEY],
        ];
    }

    public function testProcessCartShouldSetQuantityOfPriceDefinitionWhenAddingASimilarProduct(): void
    {
        $this->createProduct();
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [
                ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION => false,
                ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true,
            ],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $definition = new QuantityPriceDefinition(10, new TaxRuleCollection(), 1);

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);
        $product->setPriceDefinition($definition);
        $product->setLabel('My test product');
        $product->setQuantity(5);

        $cart = $this->cartService->getCart($token, $context);
        $this->cartService->add($cart, $product, $context);
        $this->cartService->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());

        static::assertInstanceOf(LineItem::class, $product);
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertNotNull($product->getPriceDefinition());
        static::assertInstanceOf(QuantityPriceDefinition::class, $product->getPriceDefinition());
        static::assertEquals($product->getQuantity(), $actualProduct->getQuantity());
        static::assertEquals($product->getPrice(), $this->calculator->calculate($product->getPriceDefinition(), $context));
        static::assertEquals($product, $actualProduct);
    }

    public function testProcessCartShouldReCalculateThePriceWhenAddAProductAndHasNoCustomPrice(): void
    {
        $this->createProduct();
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [
                ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION => false,
                ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true,
            ],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);
        $product->setLabel('My test product');

        $cart = $this->cartService->getCart($token, $context);
        $this->cartService->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());

        static::assertInstanceOf(LineItem::class, $product);
        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertNotNull($product->getPriceDefinition());
        static::assertInstanceOf(QuantityPriceDefinition::class, $product->getPriceDefinition());
        static::assertEquals($product->getQuantity(), $actualProduct->getQuantity());
        static::assertEquals($product->getPrice(), $this->calculator->calculate($product->getPriceDefinition(), $context));
        static::assertEquals($product, $actualProduct);
    }

    public function testProcessCartWithNulledFreeShipping(): void
    {
        $this->createProduct([
            'shippingFree' => null,
        ]);
        $token = $this->ids->create('token');
        $options = [
            SalesChannelContextService::PERMISSIONS => [
                ProductCartProcessor::SKIP_PRODUCT_STOCK_VALIDATION => false,
                ProductCartProcessor::ALLOW_PRODUCT_PRICE_OVERWRITES => true,
            ],
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create($token, TestDefaults::SALES_CHANNEL, $options);

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);
        $product->setLabel('My test product');

        $cart = $this->cartService->getCart($token, $context);
        $this->cartService->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());

        static::assertInstanceOf(LineItem::class, $actualProduct);
        static::assertInstanceOf(DeliveryInformation::class, $actualProduct->getDeliveryInformation());
        static::assertFalse($actualProduct->getDeliveryInformation()->getFreeDelivery());
    }

    public function testInactiveProductCartRemoval(): void
    {
        $context = $this->getContext();
        $this->createProduct();
        $this->getProductCart();

        $update = ['id' => $this->ids->get('product'), 'active' => false];
        static::getContainer()->get('product.repository')->upsert([$update], $context->getContext());

        $cart = $this->cartService->getCart($context->getToken(), $this->getContext(), false);

        $lineItem = $cart->get($this->ids->get('product'));
        static::assertNull($lineItem);
    }

    public function testDeletedProductCartRemoval(): void
    {
        $context = $this->getContext();
        $this->createProduct();
        $this->getProductCart();

        static::getContainer()->get('product.repository')->delete([['id' => $this->ids->get('product')]], $context->getContext());

        $cart = $this->cartService->getCart($context->getToken(), $this->getContext(), false);

        $lineItem = $cart->get($this->ids->get('product'));
        static::assertNull($lineItem);
    }

    public function testTaxIsRecalculatedOnCountryChange(): void
    {
        $deCountryId = static::getContainer()->get('country.repository')->searchIds((new Criteria())->addFilter(new EqualsFilter('iso', 'DE')), Context::createDefaultContext())->firstId();
        $customerId = $this->createCustomer($deCountryId ?? $this->getValidCountryId());
        $parameters = new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $this->ids->create('token'), customerId: $customerId);
        $context = static::getContainer()->get(SalesChannelContextService::class)->get($parameters);

        $this->createProduct([
            'taxId' => static::getContainer()->get(SystemConfigService::class)->get('core.tax.defaultTaxRate'),
        ]);

        $cart = $this->cartService->add(
            $this->cartService->getCart($context->getToken(), $context),
            static::getContainer()->get(ProductLineItemFactory::class)->create(['id' => $this->ids->get('product')], $context),
            $context
        );

        $lineItem = $cart->get($this->ids->get('product'));
        static::assertSame(0.0, $lineItem?->getPrice()?->getTaxRules()?->first()?->getTaxRate());

        $upsert = [
            'id' => $context->getCustomer()?->getDefaultShippingAddress()?->getId(),
            'countryId' => static::getContainer()->get('country.repository')->searchIds((new Criteria())->addFilter(new EqualsFilter('iso', 'CN')), $context->getContext())->firstId(),
        ];
        static::getContainer()->get('customer_address.repository')->upsert([$upsert], $context->getContext());

        $context = static::getContainer()->get(SalesChannelContextService::class)->get($parameters);
        $cart = $this->cartService->getCart($context->getToken(), $context, false);
        $lineItem = $cart->get($this->ids->get('product'));
        static::assertSame(0.0, $lineItem?->getPrice()?->getTaxRules()?->first()?->getTaxRate());
    }

    public function testProducePriceChangeAfterContextRuleChange(): void
    {
        $countryIds = $this->getCountryIds();

        static::assertIsString($countryIds[0]);

        $customerId = $this->createCustomer($countryIds[0]);

        $this->createProduct([
            'prices' => [
                [
                    'quantityStart' => 1,
                    'rule' => [
                        'id' => $this->ids->create('rule'),
                        'name' => 'Test rule',
                        'priority' => 1,
                        'conditions' => [
                            [
                                'type' => 'customerShippingCountry',
                                'value' => [
                                    'operator' => '=',
                                    'countryIds' => [$countryIds[0]],
                                ],
                            ],
                        ],
                    ],
                    'price' => [
                        [
                            'currencyId' => Defaults::CURRENCY,
                            'gross' => 50,
                            'net' => 9, 'linked' => false,
                            'listPrice' => ['gross' => 60, 'net' => 60, 'linked' => false],
                        ],
                    ],
                ],
            ],
        ]);
        $token = $this->ids->create('token');

        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create($token, TestDefaults::SALES_CHANNEL, [SalesChannelContextService::CUSTOMER_ID => $customerId]);

        static::assertNotNull($context->getCustomer());

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);

        $cart = $this->cartService->getCart($token, $context);
        $this->cartService->add($cart, $product, $context);

        $actualProduct = $cart->get($product->getId());

        static::assertSame(50.0, $actualProduct?->getPrice()?->getTotalPrice());

        static::getContainer()->get('customer_address.repository')->update([
            [
                'id' => $context->getCustomer()->getDefaultBillingAddressId(),
                'countryId' => $countryIds[0],
            ],
        ], Context::createDefaultContext());

        $context = static::getContainer()->get(SalesChannelContextFactory::class)->create($token, TestDefaults::SALES_CHANNEL, [SalesChannelContextService::CUSTOMER_ID => $customerId]);

        $cart = $this->cartService->getCart($token, $context, false);

        $actualProduct = $cart->get($product->getId());

        static::assertSame(50.0, $actualProduct?->getPrice()?->getTotalPrice());
    }

    /**
     * @return list<string>|list<array<string, string>>
     */
    private function getCountryIds(): array
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('country.repository');

        $criteria = (new Criteria())->setLimit(2)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('shippingAvailable', true))
            ->addSorting(new FieldSorting('iso'));

        return $repository->searchIds($criteria, Context::createDefaultContext())->getIds();
    }

    private function createCustomer(string $countryId): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $countryId,
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        static::getContainer()->get('customer.repository')->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }

    private function getProductCart(): Cart
    {
        $context = $this->getContext();

        $product = static::getContainer()->get(ProductLineItemFactory::class)
            ->create(['id' => $this->ids->get('product'), 'referencedId' => $this->ids->get('product')], $context);

        $cart = $this->cartService->getCart($context->getToken(), $context);

        $this->cartService->add($cart, $product, $context);

        return $cart;
    }

    private function getContext(): SalesChannelContext
    {
        $token = $this->ids->create('token');

        return static::getContainer()->get(SalesChannelContextService::class)
            ->get(new SalesChannelContextServiceParameters(TestDefaults::SALES_CHANNEL, $token));
    }

    /**
     * @param array<string, mixed> $additionalData
     */
    private function createProduct(array $additionalData = []): void
    {
        $data = [
            'id' => $this->ids->create('product'),
            'name' => 'test',
            'productNumber' => Uuid::randomHex(),
            'stock' => 10,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15, 'net' => 10, 'linked' => false],
            ],
            'purchasePrices' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 7.5, 'net' => 5, 'linked' => false],
                ['currencyId' => Uuid::randomHex(), 'gross' => 150, 'net' => 100, 'linked' => false],
            ],
            'active' => true,
            'taxId' => $this->getValidTaxId(),
            'weight' => 100,
            'height' => 101,
            'width' => 102,
            'length' => 103,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'test',
                ],
            ],
        ];

        $data = array_merge($data, $additionalData);

        static::getContainer()->get('product.repository')
            ->create([$data], Context::createDefaultContext());
    }

    /**
     * @param array<string, mixed>|null $additionalData
     */
    private function createCustomField(?array $additionalData = []): void
    {
        if ($additionalData === null) {
            $additionalData = [];
        }

        $data = array_merge([
            'id' => self::CUSTOM_FIELD_ID,
            'name' => 'lorem_ipsum',
            'type' => CustomFieldTypes::TEXT,
            'config' => [
                'componentName' => 'sw-field',
                'customFieldPosition' => 1,
                'customFieldType' => CustomFieldTypes::TEXT,
                'type' => CustomFieldTypes::TEXT,
                'label' => [
                    'en-GB' => 'lorem_ipsum',
                    'zh-CN' => 'lorem_ipsum',
                ],
            ],
        ], $additionalData);

        static::getContainer()->get('custom_field.repository')
            ->create([$data], Context::createDefaultContext());
    }

    /**
     * @param array{type: string}[]|null $features
     *
     * @return array<string, mixed>
     */
    private function createFeatureSet(?array $features = []): array
    {
        return [
            'id' => $this->ids->create('product-feature-set'),
            'translations' => [
                Defaults::LANGUAGE_SYSTEM => [
                    'name' => 'Test feature set',
                    'description' => 'Lorem ipsum dolor sit amet',
                ],
            ],
            'features' => $features,
        ];
    }

    private function createLanguage(string $id, ?string $parentId = Defaults::LANGUAGE_SYSTEM): void
    {
        $languageRepository = static::getContainer()->get('language.repository');

        $languageRepository->create(
            [
                [
                    'id' => $id,
                    'name' => \sprintf('name-%s', $id),
                    'localeId' => $this->getLocaleIdOfSystemLanguage(),
                    'parentId' => $parentId,
                    'translationCode' => [
                        'id' => self::TEST_LOCALE_ID,
                        'code' => self::TEST_LANGUAGE_LOCALE_CODE,
                        'name' => 'Test locale',
                        'territory' => 'test',
                    ],
                    'salesChannels' => [
                        ['id' => TestDefaults::SALES_CHANNEL],
                    ],
                    'salesChannelDefaultAssignments' => [
                        ['id' => TestDefaults::SALES_CHANNEL],
                    ],
                ],
            ],
            Context::createDefaultContext()
        );
    }
}
