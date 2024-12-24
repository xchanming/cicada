<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemInCategoryRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Validation\Constraint\ArrayOfUuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Tests\Unit\Core\Checkout\Cart\SalesChannel\Helper\CartRuleHelperTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(LineItemInCategoryRule::class)]
#[Group('rules')]
class LineItemInCategoryRuleTest extends TestCase
{
    use CartRuleHelperTrait;

    private LineItemInCategoryRule $rule;

    protected function setUp(): void
    {
        $this->rule = new LineItemInCategoryRule();
    }

    public function testGetName(): void
    {
        static::assertSame('cartLineItemInCategory', $this->rule->getName());
    }

    public function testGetConstraints(): void
    {
        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('operator', $ruleConstraints, 'Rule Constraint operator is not defined');
        static::assertArrayHasKey('categoryIds', $ruleConstraints, 'Rule Constraint categoryIds is not defined');
    }

    /**
     * @param array<string> $categoryIds
     * @param array<string> $lineItemCategoryIds
     */
    #[DataProvider('getLineItemScopeTestData')]
    public function testIfMatchesCorrectWithLineItemScope(
        array $categoryIds,
        string $operator,
        array $lineItemCategoryIds,
        bool $expected
    ): void {
        $this->rule->assign([
            'categoryIds' => $categoryIds,
            'operator' => $operator,
        ]);

        $match = $this->rule->match(new LineItemScope(
            $this->createLineItemWithCategories($lineItemCategoryIds),
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    public static function getLineItemScopeTestData(): \Generator
    {
        yield 'single product / equal / match category id' => [['1', '2'], Rule::OPERATOR_EQ, ['1'], true];
        yield 'single product / equal / no match' => [['1', '2'], Rule::OPERATOR_EQ, ['3'], false];
        yield 'single product / not equal / match category id' => [['1', '2'], Rule::OPERATOR_NEQ, ['3'], true];
        yield 'single product / empty / match category id' => [['1', '2'], Rule::OPERATOR_EMPTY, [], true];
        yield 'single product / empty / no match category id' => [['1', '2'], Rule::OPERATOR_EMPTY, ['3'], false];
    }

    /**
     * @param array<string> $categoryIds
     * @param array<string> $lineItemCategoryIds
     */
    #[DataProvider('getCartRuleScopeTestData')]
    public function testIfMatchesCorrectWithCartRuleScope(
        array $categoryIds,
        string $operator,
        array $lineItemCategoryIds,
        bool $expected
    ): void {
        $this->rule->assign([
            'categoryIds' => $categoryIds,
            'operator' => $operator,
        ]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithCategories(['1']),
            $this->createLineItemWithCategories($lineItemCategoryIds),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @param array<string> $categoryIds
     * @param array<string> $lineItemCategoryIds
     */
    #[DataProvider('getCartRuleScopeTestData')]
    public function testIfMatchesCorrectWithCartRuleScopeNested(
        array $categoryIds,
        string $operator,
        array $lineItemCategoryIds,
        bool $expected
    ): void {
        $this->rule->assign([
            'categoryIds' => $categoryIds,
            'operator' => $operator,
        ]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithCategories(['1']),
            $this->createLineItemWithCategories($lineItemCategoryIds),
        ]);
        $containerLineItem = $this->createContainerLineItem($lineItemCollection)->setPayloadValue('categoryIds', ['1']);
        $cart = $this->createCart(new LineItemCollection([$containerLineItem]));

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    public static function getCartRuleScopeTestData(): \Generator
    {
        yield 'multiple products / equal / match category id' => [['1', '2'], Rule::OPERATOR_EQ, ['2'], true];
        yield 'multiple products / equal / no match' => [['4', '5'], Rule::OPERATOR_EQ, ['2'], false];
        yield 'multiple products / not equal / match category id' => [['5', '6'], Rule::OPERATOR_NEQ, ['2'], true];
        yield 'multiple products / not equal / no match category id' => [['1', '2'], Rule::OPERATOR_NEQ, ['2'], false];
        yield 'multiple products / empty / match category id' => [['1', '2'], Rule::OPERATOR_EMPTY, [], true];
        yield 'multiple products / empty / no match category id' => [['1', '2'], Rule::OPERATOR_EMPTY, ['2'], false];
    }

    public function testNotAvailableOperatorIsUsed(): void
    {
        $this->rule->assign([
            'categoryIds' => ['1', '2'],
            'operator' => Rule::OPERATOR_LT,
        ]);

        $this->expectException(UnsupportedOperatorException::class);

        $this->rule->match(new LineItemScope(
            $this->createLineItemWithCategories(['3']),
            $this->createMock(SalesChannelContext::class)
        ));
    }

    public function testOnlyMatchesGoods(): void
    {
        $this->rule->assign([
            'categoryIds' => ['1'],
            'operator' => Rule::OPERATOR_NEQ,
        ]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithCategories(['1']),
            $this->createLineItem(LineItem::PROMOTION_LINE_ITEM_TYPE, 1, 'PROMO')->setGood(false),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertFalse($match);
    }

    public function testConstraints(): void
    {
        $expectedOperators = [
            Rule::OPERATOR_EQ,
            Rule::OPERATOR_NEQ,
            Rule::OPERATOR_EMPTY,
        ];

        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('operator', $ruleConstraints, 'Constraint operator not found in Rule');
        $operators = $ruleConstraints['operator'];
        static::assertEquals(new NotBlank(), $operators[0]);
        static::assertEquals(new Choice($expectedOperators), $operators[1]);

        $this->rule->assign(['operator' => Rule::OPERATOR_EQ]);
        static::assertArrayHasKey('categoryIds', $ruleConstraints, 'Constraint categoryIds not found in Rule');
        $categoryIds = $ruleConstraints['categoryIds'];
        static::assertEquals(new NotBlank(), $categoryIds[0]);
        static::assertEquals(new ArrayOfUuid(), $categoryIds[1]);
    }

    /**
     * @param array<string> $categoryIds
     */
    private function createLineItemWithCategories(array $categoryIds): LineItem
    {
        return $this->createLineItem()->setPayloadValue('categoryIds', $categoryIds);
    }
}
