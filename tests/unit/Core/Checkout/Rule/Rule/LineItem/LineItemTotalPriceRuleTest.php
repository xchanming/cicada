<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\LineItem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemTotalPriceRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(LineItemTotalPriceRule::class)]
class LineItemTotalPriceRuleTest extends TestCase
{
    public function testRuleWithExactAmountMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())
            ->assign(['amount' => 200, 'operator' => Rule::OPERATOR_EQ]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($lineItem);
        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithExactAmountNotMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 199, 'operator' => Rule::OPERATOR_EQ]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertFalse(
            $rule->match(new LineItemScope($lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($lineItem);
        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithLowerThanEqualExactAmountMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 200, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 201, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithLowerThanEqualAmountNotMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 199, 'operator' => Rule::OPERATOR_LTE]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertFalse(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithGreaterThanEqualExactAmountMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 200, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithGreaterThanEqualMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 199, 'operator' => Rule::OPERATOR_GTE]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithGreaterThanEqualNotMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 201, 'operator' => Rule::OPERATOR_GTE]);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithNotEqualMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 199, 'operator' => Rule::OPERATOR_NEQ]);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }

    public function testRuleWithNotEqualNotMatch(): void
    {
        $rule = (new LineItemTotalPriceRule())->assign(['amount' => 200, 'operator' => Rule::OPERATOR_NEQ]);

        $lineItem = (new LineItem('A', 'product'))
            ->setPrice(
                new CalculatedPrice(100, 200, new CalculatedTaxCollection(), new TaxRuleCollection())
            );

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope($lineItem, $context))
        );
    }
}
