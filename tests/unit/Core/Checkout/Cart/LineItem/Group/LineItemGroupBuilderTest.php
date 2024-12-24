<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroup;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemGroupServiceRegistry;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Cicada\Core\Checkout\Cart\LineItem\Group\Packager\LineItemGroupCountPackager;
use Cicada\Core\Checkout\Cart\LineItem\Group\Packager\LineItemGroupUnitPriceGrossPackager;
use Cicada\Core\Checkout\Cart\LineItem\Group\Packager\LineItemGroupUnitPriceNetPackager;
use Cicada\Core\Checkout\Cart\LineItem\Group\ProductLineItemProvider;
use Cicada\Core\Checkout\Cart\LineItem\Group\RulesMatcher\AnyRuleLineItemMatcher;
use Cicada\Core\Checkout\Cart\LineItem\Group\RulesMatcher\AnyRuleMatcher;
use Cicada\Core\Checkout\Cart\LineItem\Group\Sorter\LineItemGroupPriceAscSorter;
use Cicada\Core\Checkout\Cart\LineItem\Group\Sorter\LineItemGroupPriceDescSorter;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItemQuantitySplitter;
use Cicada\Core\Checkout\Cart\Price\CashRounding;
use Cicada\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\NetPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\Tax\TaxCalculator;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Container\AndRule;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Rule\FalseRule;
use Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Traits\LineItemGroupTestFixtureBehaviour;
use Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Traits\LineItemTestFixtureBehaviour;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LineItemGroupBuilder::class)]
class LineItemGroupBuilderTest extends TestCase
{
    use LineItemGroupTestFixtureBehaviour;
    use LineItemTestFixtureBehaviour;

    private const KEY_PRICE_UNIT_GROSS = 'PRICE_UNIT_GROSS';

    private const KEY_SORTER_PRICE_ASC = 'PRICE_ASC';

    private LineItemGroupBuilder $lineItemGroupBuilder;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(SalesChannelContext::class)->disableOriginalConstructor()->getMock();
        $this->context->method('getItemRounding')->willReturn(new CashRoundingConfig(2, 0.01, true));

        $quantityPriceCalculator = $this->createQuantityPriceCalculator();

        $this->lineItemGroupBuilder = new LineItemGroupBuilder(
            new LineItemGroupServiceRegistry(
                [
                    new LineItemGroupCountPackager(),
                    new LineItemGroupUnitPriceGrossPackager(),
                    new LineItemGroupUnitPriceNetPackager(),
                ],
                [
                    new LineItemGroupPriceAscSorter(),
                    new LineItemGroupPriceDescSorter(),
                ]
            ),
            new AnyRuleMatcher(new AnyRuleLineItemMatcher()),
            new LineItemQuantitySplitter($quantityPriceCalculator),
            new ProductLineItemProvider()
        );
    }

    public function testItemNotMatchRule(): void
    {
        $cart = $this->buildCart(0);

        $item1 = $this->createProductItem(10, 10, 20);

        $item1->setReferencedId($item1->getId());

        $item1->setQuantity(3);
        $item1->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection([]), 3));

        $cart->addLineItems(new LineItemCollection([$item1]));

        $ruleEntity = new RuleEntity();
        $ruleEntity->setId(Uuid::randomHex());
        $ruleEntity->setPayload(new AndRule([new FalseRule()]));

        $group = $this->buildGroup(
            self::KEY_PRICE_UNIT_GROSS,
            50,
            self::KEY_SORTER_PRICE_ASC,
            new RuleCollection([$ruleEntity])
        );

        $result = $this->lineItemGroupBuilder->findGroupPackages([$group], $cart, $this->context);
        $groupCount = $result->getGroupResult($group);

        static::assertCount(0, $groupCount);
    }

    public function testNoItemMatchGroupDefinition(): void
    {
        $cart = $this->buildCart(0);

        $item1 = $this->createProductItem(10, 10, 20);

        $item1->setReferencedId($item1->getId());

        $item1->setQuantity(3);
        $item1->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection([]), 3));

        $cart->addLineItems(new LineItemCollection([$item1]));

        $ruleEntity = new RuleEntity();
        $ruleEntity->setId(Uuid::randomHex());
        $ruleEntity->setPayload(new AndRule());

        $group = $this->buildGroup(
            self::KEY_PRICE_UNIT_GROSS,
            50,
            self::KEY_SORTER_PRICE_ASC,
            new RuleCollection([$ruleEntity])
        );

        $result = $this->lineItemGroupBuilder->findGroupPackages([$group], $cart, $this->context);
        $groupCount = $result->getGroupResult($group);

        static::assertCount(0, $groupCount);
    }

    public function testOneItemShouldGroupsCorrectly(): void
    {
        $cart = $this->buildCart(0);

        $item1 = $this->createProductItem(10, 10, 20);

        $item1->setReferencedId($item1->getId());

        $item1->setQuantity(3);
        $item1->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection([]), 3));

        $cart->addLineItems(new LineItemCollection([$item1]));

        $ruleEntity = new RuleEntity();
        $ruleEntity->setId(Uuid::randomHex());
        $ruleEntity->setPayload(new AndRule());

        $group = $this->buildGroup(
            self::KEY_PRICE_UNIT_GROSS,
            30,
            self::KEY_SORTER_PRICE_ASC,
            new RuleCollection([$ruleEntity])
        );

        $result = $this->lineItemGroupBuilder->findGroupPackages([$group], $cart, $this->context);
        $groupCount = $result->getGroupResult($group);

        static::assertCount(1, $groupCount);

        static::assertInstanceOf(LineItemGroup::class, $groupCount[0]);
        $items = $groupCount[0]->getItems();
        static::assertCount(1, $items);
        static::assertInstanceOf(LineItemQuantity::class, $items[0]);
        static::assertSame($item1->getId(), $items[0]->getLineItemId());
        static::assertEquals(3, $items[0]->getQuantity());
    }

    public function testShouldGroupsCorrectly(): void
    {
        $cart = $this->buildCart(0);

        $item1 = $this->createProductItem(10, 10, 20);
        $item2 = $this->createProductItem(20, 10, 30);
        $item3 = $this->createProductItem(40, 10, 100);

        $item1->setReferencedId($item1->getId());
        $item2->setReferencedId($item2->getId());
        $item3->setReferencedId($item3->getId());

        $item1->setQuantity(3);
        $item2->setQuantity(7);
        $item3->setQuantity(5);
        $item1->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection([]), 3));
        $item2->setPriceDefinition(new QuantityPriceDefinition(20, new TaxRuleCollection([]), 7));
        $item3->setPriceDefinition(new QuantityPriceDefinition(40, new TaxRuleCollection([]), 5));

        $cart->addLineItems(new LineItemCollection([$item1, $item2, $item3]));

        $ruleEntity = new RuleEntity();
        $ruleEntity->setId(Uuid::randomHex());
        $ruleEntity->setPayload(new AndRule());

        $group = $this->buildGroup(
            self::KEY_PRICE_UNIT_GROSS,
            70,
            self::KEY_SORTER_PRICE_ASC,
            new RuleCollection([$ruleEntity])
        );

        $result = $this->lineItemGroupBuilder->findGroupPackages([$group], $cart, $this->context);
        $groupCount = $result->getGroupResult($group);

        static::assertCount(4, $groupCount);

        static::assertInstanceOf(LineItemGroup::class, $groupCount[0]);
        $items = $groupCount[0]->getItems();
        static::assertCount(2, $items);
        static::assertInstanceOf(LineItemQuantity::class, $items[0]);
        static::assertInstanceOf(LineItemQuantity::class, $items[1]);
        static::assertEquals($item1->getId(), $items[0]->getLineItemId());
        static::assertEquals($item2->getId(), $items[1]->getLineItemId());
        static::assertEquals(3, $items[0]->getQuantity());
        static::assertEquals(2, $items[1]->getQuantity());

        static::assertInstanceOf(LineItemGroup::class, $groupCount[1]);
        $items = $groupCount[1]->getItems();
        static::assertCount(1, $items);
        static::assertInstanceOf(LineItemQuantity::class, $items[0]);
        static::assertEquals($item2->getId(), $items[0]->getLineItemId());
        static::assertEquals(4, $items[0]->getQuantity());

        static::assertInstanceOf(LineItemGroup::class, $groupCount[2]);
        $items = $groupCount[2]->getItems();
        static::assertCount(2, $items);
        static::assertInstanceOf(LineItemQuantity::class, $items[0]);
        static::assertInstanceOf(LineItemQuantity::class, $items[1]);
        static::assertEquals($item2->getId(), $items[0]->getLineItemId());
        static::assertEquals($item3->getId(), $items[1]->getLineItemId());
        static::assertEquals(1, $items[0]->getQuantity());
        static::assertEquals(2, $items[1]->getQuantity());

        static::assertInstanceOf(LineItemGroup::class, $groupCount[3]);
        $items = $groupCount[3]->getItems();
        static::assertCount(1, $items);
        static::assertInstanceOf(LineItemQuantity::class, $items[0]);
        static::assertEquals($item3->getId(), $items[0]->getLineItemId());
        static::assertEquals(2, $items[0]->getQuantity());
    }

    public function testBuildGroupCacheShouldWork(): void
    {
        $cart = $this->buildCart(0);

        $item1 = $this->createProductItem(10, 10, 20);

        $item1->setReferencedId($item1->getId());

        $item1->setQuantity(3);
        $item1->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection([]), 3));
        $cart->addLineItems(new LineItemCollection([$item1]));

        $ruleEntity = new RuleEntity();
        $ruleEntity->setId(Uuid::randomHex());
        $ruleEntity->setPayload(new AndRule());

        $group = $this->buildGroup(
            self::KEY_PRICE_UNIT_GROSS,
            30,
            self::KEY_SORTER_PRICE_ASC,
            new RuleCollection([$ruleEntity])
        );

        $result = $this->lineItemGroupBuilder->findGroupPackages([$group], $cart, $this->context);
        $groupCount = $result->getGroupResult($group);

        static::assertCount(1, $groupCount);

        static::assertInstanceOf(LineItemGroup::class, $groupCount[0]);
        $items = $groupCount[0]->getItems();
        static::assertCount(1, $items);
        static::assertInstanceOf(LineItemQuantity::class, $items[0]);
        static::assertEquals($item1->getId(), $items[0]->getLineItemId());
        static::assertEquals(3, $items[0]->getQuantity());

        $cachedResult = $this->lineItemGroupBuilder->findGroupPackages([$group], $cart, $this->context);

        static::assertEquals($result, $cachedResult);
    }

    private function buildCart(int $productCount): Cart
    {
        $products = [];

        for ($i = 1; $i <= $productCount; ++$i) {
            $products[] = $this->createProductItem(100, 0);
        }

        $cart = new Cart('token');
        $cart->addLineItems(new LineItemCollection($products));

        return $cart;
    }

    private function createQuantityPriceCalculator(): QuantityPriceCalculator
    {
        $priceRounding = new CashRounding();

        $taxCalculator = new TaxCalculator();

        return new QuantityPriceCalculator(
            new GrossPriceCalculator($taxCalculator, $priceRounding),
            new NetPriceCalculator($taxCalculator, $priceRounding),
        );
    }
}
