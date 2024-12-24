<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Hook;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\Hook\CartHook;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(CartHook::class)]
class CartHookTest extends TestCase
{
    public function testNameRespectsCartSource(): void
    {
        $cart = new Cart('test');
        $cart->setSource('test');
        $hook = new CartHook($cart, $this->createMock(SalesChannelContext::class));

        static::assertEquals('cart-test', $hook->getName());
    }

    public function testNameWithoutCartSource(): void
    {
        $cart = new Cart('test');
        $hook = new CartHook($cart, $this->createMock(SalesChannelContext::class));

        static::assertEquals('cart', $hook->getName());
    }
}
