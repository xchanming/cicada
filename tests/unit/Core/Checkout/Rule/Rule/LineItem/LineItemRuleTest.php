<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\LineItem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(LineItemRule::class)]
class LineItemRuleTest extends TestCase
{
    public function testRuleMatch(): void
    {
        $rule = (new LineItemRule())
            ->assign(['identifiers' => ['A']]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = new LineItem('A', 'product', 'A');

        static::assertTrue(
            $rule->match(new LineItemScope($lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($lineItem);

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleNotMatch(): void
    {
        $rule = (new LineItemRule())
            ->assign(['identifiers' => ['A']]);

        $context = $this->createMock(SalesChannelContext::class);

        $lineItem = new LineItem('A', 'product', 'B');

        static::assertFalse(
            $rule->match(new LineItemScope($lineItem, $context))
        );

        $cart = new Cart('test');
        $cart->add($lineItem);

        static::assertFalse(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }
}
