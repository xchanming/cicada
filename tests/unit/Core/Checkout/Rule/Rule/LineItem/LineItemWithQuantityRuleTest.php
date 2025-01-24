<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\LineItem;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemWithQuantityRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(LineItemWithQuantityRule::class)]
class LineItemWithQuantityRuleTest extends TestCase
{
    private LineItem $lineItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lineItem = (new LineItem('A', 'product', 'A', 2))
            ->setPrice(new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection(), 2));
    }

    public function testRuleWithExactAmountMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 2, 'operator' => Rule::OPERATOR_EQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithExactAmountNotMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 0, 'operator' => Rule::OPERATOR_EQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualExactAmountMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 2, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 3, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountNotMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 1, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualExactAmountMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 2, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 1, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualNotMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 3, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithNotEqualMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 1, 'operator' => Rule::OPERATOR_NEQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithNotEqualNotMatch(): void
    {
        $rule = (new LineItemWithQuantityRule())->assign(['id' => 'A', 'quantity' => 2, 'operator' => Rule::OPERATOR_NEQ]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($this->lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($this->lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
