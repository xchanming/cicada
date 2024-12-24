<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Rule;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemInProductStreamRule;
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
#[CoversClass(LineItemInProductStreamRule::class)]
#[Group('rules')]
class LineItemInProductStreamRuleTest extends TestCase
{
    use CartRuleHelperTrait;

    private LineItemInProductStreamRule $rule;

    protected function setUp(): void
    {
        $this->rule = new LineItemInProductStreamRule();
    }

    public function testGetName(): void
    {
        static::assertSame('cartLineItemInProductStream', $this->rule->getName());
    }

    public function testGetConstraints(): void
    {
        $ruleConstraints = $this->rule->getConstraints();

        static::assertArrayHasKey('operator', $ruleConstraints, 'Rule Constraint operator is not defined');
        static::assertArrayHasKey('streamIds', $ruleConstraints, 'Rule Constraint streamIds is not defined');
    }

    /**
     * @param array<string> $streamIds
     * @param array<string> $lineItemProductStreamIds
     */
    #[DataProvider('getLineItemScopeTestData')]
    public function testIfMatchesCorrectWithLineItemScope(
        array $streamIds,
        string $operator,
        array $lineItemProductStreamIds,
        bool $expected
    ): void {
        $this->rule->assign([
            'streamIds' => $streamIds,
            'operator' => $operator,
        ]);

        $match = $this->rule->match(new LineItemScope(
            $this->createLineItemWithProductStreams($lineItemProductStreamIds),
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @return array<string, array<array<string>|string|bool>>
     */
    public static function getLineItemScopeTestData(): array
    {
        return [
            'single product / equal / match stream id' => [['1', '2'], Rule::OPERATOR_EQ, ['1'], true],
            'single product / equal / no match' => [['1', '2'], Rule::OPERATOR_EQ, ['3'], false],
            'single product / not equal / match stream id' => [['1', '2'], Rule::OPERATOR_NEQ, ['3'], true],
            'single product / empty / match stream id' => [['1', '2'], Rule::OPERATOR_EMPTY, [], true],
            'single product / empty / no match stream id' => [['1', '2'], Rule::OPERATOR_EMPTY, ['3'], false],
        ];
    }

    /**
     * @param array<string> $streamIds
     * @param array<string> $lineItemCategoryIds
     */
    #[DataProvider('getCartRuleScopeTestData')]
    public function testIfMatchesCorrectWithCartRuleScope(
        array $streamIds,
        string $operator,
        array $lineItemCategoryIds,
        bool $expected
    ): void {
        $this->rule->assign([
            'streamIds' => $streamIds,
            'operator' => $operator,
        ]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithProductStreams(['1']),
            $this->createLineItemWithProductStreams($lineItemCategoryIds),
        ]);

        $cart = $this->createCart($lineItemCollection);

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @param array<string> $streamIds
     * @param array<string> $lineItemCategoryIds
     */
    #[DataProvider('getCartRuleScopeTestData')]
    public function testIfMatchesCorrectWithCartRuleScopeNested(
        array $streamIds,
        string $operator,
        array $lineItemCategoryIds,
        bool $expected
    ): void {
        $this->rule->assign([
            'streamIds' => $streamIds,
            'operator' => $operator,
        ]);

        $lineItemCollection = new LineItemCollection([
            $this->createLineItemWithProductStreams(['1']),
            $this->createLineItemWithProductStreams($lineItemCategoryIds),
        ]);
        $containerLineItem = $this->createContainerLineItem($lineItemCollection)->setPayloadValue('streamIds', ['1']);
        $cart = $this->createCart(new LineItemCollection([$containerLineItem]));

        $match = $this->rule->match(new CartRuleScope(
            $cart,
            $this->createMock(SalesChannelContext::class)
        ));

        static::assertSame($expected, $match);
    }

    /**
     * @return array<string, array<array<string>|string|bool>>
     */
    public static function getCartRuleScopeTestData(): array
    {
        return [
            'multiple products / equal / match stream id' => [['1', '2'], Rule::OPERATOR_EQ, ['2'], true],
            'multiple products / equal / no match' => [['4', '5'], Rule::OPERATOR_EQ, ['2'], false],
            'multiple products / not equal / match stream id' => [['5', '6'], Rule::OPERATOR_NEQ, ['2'], true],
            'multiple products / not equal / no match stream id' => [['1', '2'], Rule::OPERATOR_NEQ, ['2'], false],
            'multiple products / empty / match stream id' => [['1', '2'], Rule::OPERATOR_EMPTY, [], true],
            'multiple products / empty / no match stream id' => [['1', '2'], Rule::OPERATOR_EMPTY, ['2'], false],
        ];
    }

    public function testNotAvailableOperatorIsUsed(): void
    {
        $this->rule->assign([
            'streamIds' => ['1', '2'],
            'operator' => Rule::OPERATOR_LT,
        ]);

        $this->expectException(UnsupportedOperatorException::class);

        $this->rule->match(new LineItemScope(
            $this->createLineItemWithProductStreams(['3']),
            $this->createMock(SalesChannelContext::class)
        ));
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
        static::assertArrayHasKey('streamIds', $ruleConstraints, 'Constraint streamIds not found in Rule');
        $streamIds = $ruleConstraints['streamIds'];
        static::assertEquals(new NotBlank(), $streamIds[0]);
        static::assertEquals(new ArrayOfUuid(), $streamIds[1]);
    }

    /**
     * @param array<string> $streamIds
     */
    private function createLineItemWithProductStreams(array $streamIds): LineItem
    {
        return $this->createLineItem()->setPayloadValue('streamIds', $streamIds);
    }
}
