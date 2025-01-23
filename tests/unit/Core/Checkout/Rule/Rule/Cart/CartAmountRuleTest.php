<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\Cart;

use Cicada\Core\Checkout\Cart\Rule\CartAmountRule;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\CheckoutRuleScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Exception\UnsupportedOperatorException;
use Cicada\Core\Framework\Rule\RuleConfig;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('fundamentals@after-sales')]
#[CoversClass(CartAmountRule::class)]
class CartAmountRuleTest extends TestCase
{
    public function testRuleWithExactAmountMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 275, 'operator' => CartAmountRule::OPERATOR_EQ]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithExactAmountNotMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 0, 'operator' => CartAmountRule::OPERATOR_EQ]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualExactAmountMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 275, 'operator' => CartAmountRule::OPERATOR_LTE]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 300, 'operator' => CartAmountRule::OPERATOR_LTE]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountNotMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 274, 'operator' => CartAmountRule::OPERATOR_LTE]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualExactAmountMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 275, 'operator' => CartAmountRule::OPERATOR_GTE]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualAmountMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 100, 'operator' => CartAmountRule::OPERATOR_GTE]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithGreaterThanEqualAmountNotMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 276, 'operator' => CartAmountRule::OPERATOR_GTE]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleNotEqualAmountMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 0, 'operator' => CartAmountRule::OPERATOR_NEQ]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleNotEqualAmountNotMatch(): void
    {
        $rule = (new CartAmountRule())->assign(['amount' => 275, 'operator' => CartAmountRule::OPERATOR_NEQ]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    #[DataProvider('unsupportedOperators')]
    public function testUnsupportedOperators(string $operator): void
    {
        $this->expectException(UnsupportedOperatorException::class);

        $rule = (new CartAmountRule())->assign(['amount' => 100, 'operator' => $operator]);

        $cart = Generator::createCart();
        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    /**
     * @return array<string[]>
     */
    public static function unsupportedOperators(): array
    {
        return [
            ['random'],
            [''],
        ];
    }

    public function testMatchShouldReturnFalseScopeIsNotCartRuleScope(): void
    {
        $ruleScope = new CheckoutRuleScope($this->createMock(SalesChannelContext::class));
        $cartAmountRule = new CartAmountRule();

        static::assertFalse($cartAmountRule->match($ruleScope));
    }

    public function testGetConstraints(): void
    {
        $result = (new CartAmountRule())->getConstraints();

        static::assertArrayHasKey('amount', $result);
        static::assertIsArray($result['amount']);

        static::assertArrayHasKey('operator', $result);
        static::assertIsArray($result['operator']);
    }

    public function testGetConfig(): void
    {
        $data = (new CartAmountRule())->getConfig()->getData();

        static::assertSame(RuleConfig::OPERATOR_SET_NUMBER, $data['operatorSet']['operators']);
        static::assertSame('amount', $data['fields']['amount']['name']);
    }
}
