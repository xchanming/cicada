<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Subscriber;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\ListPrice;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;
use Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CheapestPrice;
use Cicada\Core\Content\Product\ProductDefinition;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Product\ProductEvents;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Content\Product\Subscriber\ProductSubscriber;
use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Cicada\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Cicada\Core\Content\Property\PropertyGroupCollection;
use Cicada\Core\Content\Property\PropertyGroupEntity;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\SystemSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Cicada\Core\Framework\DataAbstractionLayer\PartialEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ProductLoadedSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testExtensionSubscribesToProductLoaded(): void
    {
        static::assertArrayHasKey(ProductEvents::PRODUCT_LOADED_EVENT, ProductSubscriber::getSubscribedEvents());
        static::assertArrayHasKey('sales_channel.product.loaded', ProductSubscriber::getSubscribedEvents());
        static::assertIsString(ProductSubscriber::getSubscribedEvents()[ProductEvents::PRODUCT_LOADED_EVENT]);
        static::assertIsString(ProductSubscriber::getSubscribedEvents()['sales_channel.product.loaded']);
    }

    public function testCheapestPriceOnSalesChannelProductEntity(): void
    {
        $ids = new IdsCollection();

        static::getContainer()->get('product.repository')
            ->create([
                (new ProductBuilder($ids, 'p.1'))
                    ->price(130)
                    ->prices('rule-a', 150)
                    ->visibility()
                    ->build(),
            ], Context::createDefaultContext());

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $productEntity = static::getContainer()
            ->get('sales_channel.product.repository')
            ->search(new Criteria([$ids->get('p.1')]), $salesChannelContext)
            ->first();

        static::assertInstanceOf(SalesChannelProductEntity::class, $productEntity);

        static::assertInstanceOf(CheapestPrice::class, $productEntity->getCheapestPrice());
    }

    public function testCheapestPriceOnSalesChannelProductEntityPartial(): void
    {
        $ids = new IdsCollection();

        static::getContainer()->get('product.repository')
            ->create([
                (new ProductBuilder($ids, 'p.1'))
                    ->price(130)
                    ->prices('rule-a', 150)
                    ->visibility()
                    ->build(),
            ], Context::createDefaultContext());

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $criteria = new Criteria([$ids->get('p.1')]);
        $criteria->addFields(['id', 'cheapestPrice', 'taxId', 'price']);

        $productEntity = static::getContainer()
            ->get('sales_channel.product.repository')
            ->search($criteria, $salesChannelContext)
            ->first();

        static::assertNotNull($productEntity);
        static::assertInstanceOf(CheapestPrice::class, $productEntity->get('cheapestPrice'));
        static::assertInstanceOf(CalculatedCheapestPrice::class, $productEntity->get('calculatedCheapestPrice'));
    }

    /**
     * @param array<mixed> $product
     * @param array<mixed> $expected
     * @param array<mixed> $unexpected
     */
    #[DataProvider('propertyCases')]
    public function testSortProperties(array $product, array $expected, array $unexpected, Criteria $criteria): void
    {
        static::getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $criteria->setIds([$product['id']])
            ->addAssociation('properties.group');

        $productEntity = static::getContainer()
            ->get('sales_channel.product.repository')
            ->search($criteria, $salesChannelContext)
            ->first();

        static::assertInstanceOf(SalesChannelProductEntity::class, $productEntity);

        $subscriber = static::getContainer()->get(ProductSubscriber::class);
        $productLoadedEvent = new EntityLoadedEvent(
            static::getContainer()->get(ProductDefinition::class),
            [$productEntity],
            Context::createDefaultContext()
        );
        $subscriber->loaded($productLoadedEvent);

        $sortedPropertiesCollection = $productEntity->getSortedProperties();

        static::assertInstanceOf(PropertyGroupCollection::class, $sortedPropertiesCollection);

        $sortedProperties = $sortedPropertiesCollection->getElements();

        foreach ($expected as $expectedGroupKey => $expectedGroup) {
            $optionElementsCollection = $sortedProperties[$expectedGroupKey]->getOptions();

            static::assertInstanceOf(PropertyGroupOptionCollection::class, $optionElementsCollection);
            $optionElements = $optionElementsCollection->getElements();

            static::assertEquals($expectedGroup['name'], $sortedProperties[$expectedGroupKey]->getName());
            static::assertEquals($expectedGroup['id'], $sortedProperties[$expectedGroupKey]->getId());
            static::assertEquals(\array_keys($expectedGroup['options']), \array_keys($optionElements));

            foreach ($expectedGroup['options'] as $optionId => $option) {
                static::assertEquals($option['id'], $optionElements[$optionId]->getId());
                static::assertEquals($option['name'], $optionElements[$optionId]->getName());
            }
        }

        foreach ($unexpected as $unexpectedGroup) {
            static::assertArrayNotHasKey($unexpectedGroup['id'], $sortedProperties);
        }
    }

    /**
     * @param array<mixed> $product
     * @param array<mixed> $expected
     * @param array<mixed> $unexpected
     */
    #[DataProvider('propertyCases')]
    public function testSortPropertiesPartial(array $product, array $expected, array $unexpected, Criteria $criteria): void
    {
        static::getContainer()->get('product.repository')
            ->create([$product], Context::createDefaultContext());

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $criteria->setIds([$product['id']])
            ->addAssociation('properties.group')
            ->addFields(['properties', 'price']);

        $productEntity = static::getContainer()
            ->get('sales_channel.product.repository')
            ->search($criteria, $salesChannelContext)
            ->first();

        static::assertNotNull($productEntity);

        $sortedProperties = $productEntity->get('sortedProperties');
        static::assertInstanceOf(PropertyGroupCollection::class, $sortedProperties);
        $sortedProperties = $sortedProperties->getElements();

        foreach ($expected as $expectedGroupKey => $expectedGroup) {
            $sortedProperty = $sortedProperties[$expectedGroupKey];
            static::assertInstanceOf(PropertyGroupEntity::class, $sortedProperty);

            $optionElements = $sortedProperty->get('options');
            static::assertInstanceOf(PropertyGroupOptionCollection::class, $optionElements);
            $optionElements = $optionElements->getElements();

            static::assertEquals($expectedGroup['name'], $sortedProperty->get('name'));
            static::assertEquals($expectedGroup['id'], $sortedProperty->getId());
            static::assertEquals(\array_keys($expectedGroup['options']), \array_keys($optionElements));

            foreach ($expectedGroup['options'] as $optionId => $option) {
                $optionElement = $optionElements[$optionId];
                static::assertInstanceOf(PropertyGroupOptionEntity::class, $optionElement);

                static::assertEquals($option['id'], $optionElement->getId());
                static::assertEquals($option['name'], $optionElement->get('name'));
            }
        }

        foreach ($unexpected as $unexpectedGroup) {
            static::assertArrayNotHasKey($unexpectedGroup['id'], $sortedProperties);
        }
    }

    /**
     * @return array<mixed>
     */
    public static function propertyCases(): array
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
        ];

        return [
            [
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('bitter'),
                            'name' => 'bitter',
                            'groupId' => $ids->get('taste'),
                            'group' => ['id' => $ids->get('taste'), 'name' => 'taste'],
                        ],
                        [
                            'id' => $ids->get('sweet'),
                            'name' => 'sweet',
                            'groupId' => $ids->get('taste'),
                            'group' => ['id' => $ids->get('taste'), 'name' => 'taste'],
                        ],
                        [
                            'id' => $ids->get('hiddenValue'),
                            'name' => 'hiddenValue',
                            'groupId' => $ids->get('hidden'),
                            'group' => ['id' => $ids->get('hidden'), 'name' => 'hidden', 'visibleOnProductDetailPage' => false],
                        ],
                    ],
                ]),
                [
                    $ids->get('taste') => [
                        'id' => $ids->get('taste'),
                        'name' => 'taste',
                        'options' => [
                            $ids->get('bitter') => [
                                'id' => $ids->get('bitter'),
                                'name' => 'bitter',
                            ],
                            $ids->get('sweet') => [
                                'id' => $ids->get('sweet'),
                                'name' => 'sweet',
                            ],
                        ],
                    ],
                ],
                [
                    [
                        'id' => $ids->get('hidden'),
                        'name' => 'hidden',
                        'visibleOnProductDetailPage' => false,
                        'options' => [
                            $ids->get('hiddenValue') => [
                                'id' => $ids->get('hiddenValue'),
                                'name' => 'hiddenValue',
                            ],
                        ],
                    ],
                ],
                new Criteria(),
            ],
            [
                array_merge($defaults, [
                    'properties' => [
                        [
                            'id' => $ids->get('bitter'),
                            'name' => 'bitter',
                            'groupId' => $ids->get('taste'),
                            'group' => ['id' => $ids->get('taste'), 'name' => 'taste'],
                        ],
                        [
                            'id' => $ids->get('sweet'),
                            'name' => 'sweet',
                            'groupId' => $ids->get('taste'),
                            'group' => ['id' => $ids->get('taste'), 'name' => 'taste'],
                        ],
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'groupId' => $ids->get('color'),
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('leather'),
                            'name' => 'leather',
                            'groupId' => $ids->get('material'),
                            'group' => ['id' => $ids->get('material'), 'name' => 'material'],
                        ],
                    ],
                ]),
                [
                    $ids->get('color') => [
                        'id' => $ids->get('color'),
                        'name' => 'color',
                        'options' => [
                            $ids->get('red') => [
                                'id' => $ids->get('red'),
                                'name' => 'red',
                            ],
                        ],
                    ],
                    $ids->get('material') => [
                        'id' => $ids->get('material'),
                        'name' => 'material',
                        'options' => [
                            $ids->get('leather') => [
                                'id' => $ids->get('leather'),
                                'name' => 'leather',
                            ],
                        ],
                    ],
                    $ids->get('taste') => [
                        'id' => $ids->get('taste'),
                        'name' => 'taste',
                        'options' => [
                            $ids->get('bitter') => [
                                'id' => $ids->get('bitter'),
                                'name' => 'bitter',
                            ],
                            $ids->get('sweet') => [
                                'id' => $ids->get('sweet'),
                                'name' => 'sweet',
                            ],
                        ],
                    ],
                ],
                [],
                new Criteria(),
            ],
        ];
    }

    /**
     * @param array<mixed> $product
     * @param array<mixed> $expected
     * @param non-empty-list<string> $languageChain
     */
    #[DataProvider('variationCases')]
    public function testVariation(array $product, array $expected, array $languageChain, Criteria $criteria, bool $sort, string $languageId): void
    {
        static::getContainer()
            ->get('language.repository')
            ->create([
                [
                    'id' => $languageId,
                    'name' => 'sub_en',
                    'parentId' => Defaults::LANGUAGE_SYSTEM,
                    'localeId' => $this->getLocaleIdOfSystemLanguage(),
                ],
            ], Context::createDefaultContext());

        foreach ($languageChain as &$language) {
            if ($language === 'zh-CN') {
                $language = $this->getZhCnLanguageId();
            }
        }

        $productId = $product['id'];
        $context = Context::createDefaultContext();

        static::getContainer()->get('product.repository')
            ->create([$product], $context);

        $context = new Context(
            new SystemSource(),
            [],
            Defaults::CURRENCY,
            $languageChain
        );

        $criteria->setIds([$productId]);

        $productEntity = static::getContainer()
            ->get('product.repository')
            ->search($criteria, $context)
            ->first();
        static::assertInstanceOf(ProductEntity::class, $productEntity);
        $subscriber = static::getContainer()->get(ProductSubscriber::class);
        $productLoadedEvent = new EntityLoadedEvent(static::getContainer()->get(ProductDefinition::class), [$productEntity], $context);
        $subscriber->loaded($productLoadedEvent);

        $variation = $productEntity->getVariation();

        if ($sort) {
            sort($variation);
            sort($expected);
        }

        static::assertEquals($expected, $variation);
    }

    /**
     * @return array<mixed>
     */
    public static function variationCases(): array
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
        ];

        return [
            0 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'name' => 'xl',
                            'group' => ['id' => $ids->get('size'), 'name' => 'size'],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'name' => 'slim fit',
                            'group' => ['id' => $ids->get('fit'), 'name' => 'fit'],
                        ],
                    ],
                ]),
                [],
                [Defaults::LANGUAGE_SYSTEM],
                new Criteria(),
                false,
                $ids->get('language'),
            ],
            1 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'name' => 'xl',
                            'group' => ['id' => $ids->get('size'), 'name' => 'size'],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'name' => 'slim fit',
                            'group' => ['id' => $ids->get('fit'), 'name' => 'fit'],
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'size', 'option' => 'xl'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                ],
                [Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                true,
                $ids->get('language'),
            ],
            2 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                    'zh-CN' => ['name' => 'farbe'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                                'zh-CN' => ['name' => 'rot'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                    'zh-CN' => ['name' => 'größe'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                                'zh-CN' => ['name' => 'extra gross'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                    'zh-CN' => ['name' => 'passform'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                                'zh-CN' => ['name' => 'schmal'],
                            ],
                        ],
                    ],
                ]),
                [
                    ['group' => 'farbe', 'option' => 'rot'],
                    ['group' => 'größe', 'option' => 'extra gross'],
                    ['group' => 'passform', 'option' => 'schmal'],
                ],
                ['zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                true,
                $ids->get('language'),
            ],
            3 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                            ],
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'size', 'option' => 'xl'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                ],
                ['zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                true,
                $ids->get('language'),
            ],
            4 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                            ],
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'size', 'option' => 'xl'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                ],
                [$ids->get('language'), 'zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                true,
                $ids->get('language'),
            ],
            5 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                    'zh-CN' => ['name' => 'farbe'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                                $ids->get('language') => ['name' => 'der'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                    'zh-CN' => ['name' => 'größe'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                                $ids->get('language') => ['name' => 'lx'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                    'zh-CN' => ['name' => 'passform'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                                $ids->get('language') => ['name' => 'tif mils'],
                            ],
                        ],
                    ],
                ]),
                [
                    ['group' => 'farbe', 'option' => 'der'],
                    ['group' => 'größe', 'option' => 'lx'],
                    ['group' => 'passform', 'option' => 'tif mils'],
                ],
                [$ids->get('language'), 'zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                true,
                $ids->get('language'),
            ],
            6 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'name' => 'xl',
                            'group' => ['id' => $ids->get('size'), 'name' => 'size'],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'name' => 'slim fit',
                            'group' => ['id' => $ids->get('fit'), 'name' => 'fit'],
                        ],
                    ],
                    'configuratorGroupConfig' => [
                        [
                            'id' => $ids->get('color'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('size'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('fit'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                    ['group' => 'size', 'option' => 'xl'],
                ],
                [Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                false,
                $ids->get('language'),
            ],
            7 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                    'zh-CN' => ['name' => 'farbe'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                                'zh-CN' => ['name' => 'rot'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                    'zh-CN' => ['name' => 'größe'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                                'zh-CN' => ['name' => 'extra gross'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                    'zh-CN' => ['name' => 'passform'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                                'zh-CN' => ['name' => 'schmal'],
                            ],
                        ],
                    ],
                    'configuratorGroupConfig' => [
                        [
                            'id' => $ids->get('color'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('size'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('fit'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                    ],
                ]),
                [
                    ['group' => 'farbe', 'option' => 'rot'],
                    ['group' => 'größe', 'option' => 'extra gross'],
                    ['group' => 'passform', 'option' => 'schmal'],
                ],
                ['zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                false,
                $ids->get('language'),
            ],
            8 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                            ],
                        ],
                    ],
                    'configuratorGroupConfig' => [
                        [
                            'id' => $ids->get('color'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('size'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('fit'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                    ['group' => 'size', 'option' => 'xl'],
                ],
                ['zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                false,
                $ids->get('language'),
            ],
            9 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                            ],
                        ],
                    ],
                    'configuratorGroupConfig' => [
                        [
                            'id' => $ids->get('color'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('size'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('fit'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                    ['group' => 'size', 'option' => 'xl'],
                ],
                [$ids->get('language'), 'zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                false,
                $ids->get('language'),
            ],
            10 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'group' => [
                                'id' => $ids->get('color'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'color'],
                                    $ids->get('language') => ['name' => 'foo'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'red'],
                                $ids->get('language') => ['name' => 'der'],
                            ],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'group' => [
                                'id' => $ids->get('size'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'size'],
                                    $ids->get('language') => ['name' => 'bar'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'xl'],
                                $ids->get('language') => ['name' => 'lx'],
                            ],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'group' => [
                                'id' => $ids->get('fit'),
                                'translations' => [
                                    Defaults::LANGUAGE_SYSTEM => ['name' => 'fit'],
                                    $ids->get('language') => ['name' => 'baz'],
                                ],
                            ],
                            'translations' => [
                                Defaults::LANGUAGE_SYSTEM => ['name' => 'slim fit'],
                                $ids->get('language') => ['name' => 'tif mils'],
                            ],
                        ],
                    ],
                    'configuratorGroupConfig' => [
                        [
                            'id' => $ids->get('color'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('size'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('fit'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                    ],
                ]),
                [
                    ['group' => 'bar', 'option' => 'lx'],
                    ['group' => 'baz', 'option' => 'tif mils'],
                    ['group' => 'foo', 'option' => 'der'],
                ],
                [$ids->get('language'), 'zh-CN', Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                false,
                $ids->get('language'),
            ],
            11 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'name' => 'xl',
                            'group' => ['id' => $ids->get('size'), 'name' => 'size'],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'name' => 'slim fit',
                            'group' => ['id' => $ids->get('fit'), 'name' => 'fit'],
                        ],
                    ],
                    'configuratorGroupConfig' => [
                        [
                            'id' => $ids->get('fit'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('color'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                        [
                            'id' => $ids->get('size'),
                            'representation' => 'box',
                            'expressionForListings' => true,
                        ],
                    ],
                ]),
                [
                    ['group' => 'color', 'option' => 'red'],
                    ['group' => 'fit', 'option' => 'slim fit'],
                    ['group' => 'size', 'option' => 'xl'],
                ],
                [Defaults::LANGUAGE_SYSTEM],
                (new Criteria())->addAssociation('options.group'),
                false,
                $ids->get('language'),
            ],
        ];
    }

    /**
     * @param array<mixed> $product
     * @param array<string, string> $expected
     */
    #[DataProvider('optionCases')]
    public function testOptionSorting(array $product, array $expected, Criteria $criteria): void
    {
        $productId = $product['id'];
        $context = Context::createDefaultContext();

        static::getContainer()->get('product.repository')
            ->create([$product], $context);

        $context = new Context(
            new SystemSource(),
            [],
            Defaults::CURRENCY,
            [Defaults::LANGUAGE_SYSTEM]
        );

        $criteria->setIds([$productId]);

        /** @var ProductEntity $productEntity */
        $productEntity = static::getContainer()
            ->get('product.repository')
            ->search($criteria, $context)
            ->first();

        /** @var PropertyGroupOptionCollection $options */
        $options = $productEntity->getOptions();

        static::assertInstanceOf(PropertyGroupOptionCollection::class, $options);

        $names = $options->map(fn (PropertyGroupOptionEntity $option) => [
            'name' => $option->getName(),
        ]);

        static::assertEquals($expected, \array_values($names));
    }

    /**
     * @return array<mixed>
     */
    public static function optionCases(): array
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
        ];

        $optionsAscCriteria = (new Criteria())->addAssociation('options.group');
        $optionsAscCriteria->getAssociation('options')->addSorting(new FieldSorting('name', 'ASC'));

        $optionsDescCriteria = (new Criteria())->addAssociation('options.group');
        $optionsDescCriteria->getAssociation('options')->addSorting(new FieldSorting('name', 'DESC'));

        return [
            1 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'name' => 'xl',
                            'group' => ['id' => $ids->get('size'), 'name' => 'size'],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'name' => 'slim fit',
                            'group' => ['id' => $ids->get('fit'), 'name' => 'fit'],
                        ],
                    ],
                ]),
                [
                    ['name' => 'red'],
                    ['name' => 'slim fit'],
                    ['name' => 'xl'],
                ],
                $optionsAscCriteria,
            ],
            2 => [
                array_merge($defaults, [
                    'options' => [
                        [
                            'id' => $ids->get('red'),
                            'name' => 'red',
                            'group' => ['id' => $ids->get('color'), 'name' => 'color'],
                        ],
                        [
                            'id' => $ids->get('xl'),
                            'name' => 'xl',
                            'group' => ['id' => $ids->get('size'), 'name' => 'size'],
                        ],
                        [
                            'id' => $ids->get('slim-fit'),
                            'name' => 'slim fit',
                            'group' => ['id' => $ids->get('fit'), 'name' => 'fit'],
                        ],
                    ],
                ]),
                [
                    ['name' => 'xl'],
                    ['name' => 'slim fit'],
                    ['name' => 'red'],
                ],
                $optionsDescCriteria,
            ],
        ];
    }

    public function testListPrices(): void
    {
        $ids = new IdsCollection();

        $taxId = static::getContainer()->get(Connection::class)
            ->fetchOne('SELECT LOWER(HEX(id)) FROM tax LIMIT 1');

        static::getContainer()->get('currency.repository')
            ->create([
                [
                    'id' => $ids->create('currency'),
                    'name' => 'test',
                    'shortName' => 'test',
                    'factor' => 1.5,
                    'symbol' => 'XXX',
                    'isoCode' => 'XX',
                    'decimalPrecision' => 3,
                    'itemRounding' => $this->objectToArray(new CashRoundingConfig(3, 0.01, true)),
                    'totalRounding' => $this->objectToArray(new CashRoundingConfig(3, 0.01, true)),
                ],
            ], Context::createDefaultContext());

        $defaults = [
            'id' => 1,
            'name' => 'test',
            'stock' => 10,
            'taxId' => $taxId,
            'visibilities' => [
                ['salesChannelId' => TestDefaults::SALES_CHANNEL, 'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL],
            ],
        ];

        $cases = [
            new ListPriceTestCase(100, 90, 200, 90, 50, CartPrice::TAX_STATE_GROSS, -100, 100, 200),
            new ListPriceTestCase(100, 90, 200, 135, 33.33, CartPrice::TAX_STATE_NET, -45, 90, 135),
            new ListPriceTestCase(100, 90, 200, 135, 33.33, CartPrice::TAX_STATE_FREE, -45, 90, 135),

            new ListPriceTestCase(100, 90, 200, 90, 50, CartPrice::TAX_STATE_GROSS, -100, 100, 200, $ids->get('currency'), $ids->get('currency')),
            new ListPriceTestCase(100, 90, 200, 135, 33.33, CartPrice::TAX_STATE_NET, -45, 90, 135, $ids->get('currency'), $ids->get('currency')),
            new ListPriceTestCase(100, 90, 200, 135, 33.33, CartPrice::TAX_STATE_FREE, -45, 90, 135, $ids->get('currency'), $ids->get('currency')),

            new ListPriceTestCase(100, 90, 200, 90, 50, CartPrice::TAX_STATE_GROSS, -150, 150, 300, Defaults::CURRENCY, $ids->get('currency')),
            new ListPriceTestCase(100, 90, 200, 135, 33.33, CartPrice::TAX_STATE_NET, -67.5, 135, 202.5, Defaults::CURRENCY, $ids->get('currency')),
            new ListPriceTestCase(100, 90, 200, 135, 33.33, CartPrice::TAX_STATE_FREE, -67.5, 135, 202.5, Defaults::CURRENCY, $ids->get('currency')),
        ];

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        foreach ($cases as $i => $case) {
            // prepare currency factor calculation
            $factor = 1;
            if ($case->usedCurrency !== Defaults::CURRENCY) {
                $factor = 1.5;
            }

            $context->getContext()->assign(['currencyFactor' => $factor]);
            $context->getCurrency()->setId($case->usedCurrency);

            // test different tax states
            $context->setTaxState($case->taxState);

            // create a new product for this case
            $id = $ids->create('product-' . $i);

            $price = [
                [
                    'currencyId' => $case->currencyId,
                    'gross' => $case->gross,
                    'net' => $case->net,
                    'linked' => false,
                    'listPrice' => [
                        'gross' => $case->wasGross,
                        'net' => $case->wasNet,
                        'linked' => false,
                    ],
                ],
            ];
            if ($case->currencyId !== Defaults::CURRENCY) {
                $price[] = [
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 1,
                    'net' => 1,
                    'linked' => false,
                ];
            }

            $data = array_merge($defaults, [
                'id' => $id,
                'productNumber' => $id,
                'price' => $price,
            ]);

            static::getContainer()->get('product.repository')
                ->create([$data], Context::createDefaultContext());

            $product = static::getContainer()->get('sales_channel.product.repository')
                ->search(new Criteria([$id]), $context)
                ->get($id);

            static::assertInstanceOf(SalesChannelProductEntity::class, $product);

            $price = $product->getCalculatedPrice();

            static::assertInstanceOf(ListPrice::class, $price->getListPrice());

            static::assertEquals($case->expectedPrice, $price->getUnitPrice());
            static::assertEquals($case->expectedWas, $price->getListPrice()->getPrice());

            static::assertEquals($case->percentage, $price->getListPrice()->getPercentage());
            static::assertEquals($case->discount, $price->getListPrice()->getDiscount());

            $partialCriteria = new Criteria([$id]);
            $partialCriteria->addFields(['price', 'taxId']);
            $product = static::getContainer()->get('sales_channel.product.repository')
                ->search($partialCriteria, $context)
                ->get($id);

            static::assertInstanceOf(PartialEntity::class, $product);

            $price = $product->get('calculatedPrice');

            static::assertInstanceOf(CalculatedPrice::class, $price);
            static::assertInstanceOf(ListPrice::class, $price->getListPrice());

            static::assertEquals($case->expectedPrice, $price->getUnitPrice());
            static::assertEquals($case->expectedWas, $price->getListPrice()->getPrice());

            static::assertEquals($case->percentage, $price->getListPrice()->getPercentage());
            static::assertEquals($case->discount, $price->getListPrice()->getDiscount());
        }
    }

    /**
     * @throws \JsonException
     *
     * @return array<mixed>
     */
    private function objectToArray(object $obj): array
    {
        $jsonString = \json_encode($obj, \JSON_THROW_ON_ERROR);

        return \json_decode($jsonString, true, 512, \JSON_THROW_ON_ERROR);
    }
}

/**
 * @internal
 */
class ListPriceTestCase
{
    public function __construct(
        public float $gross,
        public float $net,
        public float $wasGross,
        public float $wasNet,
        public float $percentage,
        public string $taxState,
        public float $discount,
        public float $expectedPrice,
        public float $expectedWas,
        public string $currencyId = Defaults::CURRENCY,
        public string $usedCurrency = Defaults::CURRENCY
    ) {
    }
}
