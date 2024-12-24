<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Rule\Rule\LineItem;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Rule\CartRuleScope;
use Cicada\Core\Checkout\Cart\Rule\LineItemOfTypeRule;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[Package('services-settings')]
#[CoversClass(LineItemOfTypeRule::class)]
class LineItemOfTypeRuleTest extends TestCase
{
    public function testRuleWithProductTypeMatch(): void
    {
        $rule = (new LineItemOfTypeRule())->assign(['lineItemType' => LineItem::PRODUCT_LINE_ITEM_TYPE]);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertTrue(
            $rule->match(new LineItemScope(new LineItem('A', 'product'), $context))
        );

        $cart = new Cart('test');
        $cart->add(new LineItem('A', 'product'));

        static::assertTrue(
            $rule->match(new CartRuleScope($cart, $context))
        );
    }

    public function testRuleWithProductTypeNotMatch(): void
    {
        $rule = (new LineItemOfTypeRule())->assign(['lineItemType' => 'voucher']);

        $context = $this->createMock(SalesChannelContext::class);

        static::assertFalse(
            $rule->match(new LineItemScope(new LineItem('A', 'product'), $context))
        );

        $cart = new Cart('test');
        $cart->add(new LineItem('A', 'product'));

        $scope = new CartRuleScope($cart, $context);

        static::assertFalse($rule->match($scope));
    }
}
