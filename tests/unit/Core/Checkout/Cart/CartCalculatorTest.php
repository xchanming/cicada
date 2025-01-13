<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartBehavior;
use Cicada\Core\Checkout\Cart\CartCalculator;
use Cicada\Core\Checkout\Cart\CartContextHasher;
use Cicada\Core\Checkout\Cart\CartRuleLoader;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\RuleLoaderResult;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[CoversClass(CartCalculator::class)]
#[Package('checkout')]
class CartCalculatorTest extends TestCase
{
    public const EXPECTED_HASH = '7c9a2b636e57aaef19cad2feadf213083030bb22466653c1171f7ba56511ec03';

    public function testCalculate(): void
    {
        $context = Generator::generateSalesChannelContext();
        $behavior = new CartBehavior($context->getPermissions());
        $cart = $this->getCart();
        $result = new RuleLoaderResult($cart, new RuleCollection());

        $cartRuleLoader = $this->createMock(CartRuleLoader::class);
        $cartRuleLoader
            ->expects(static::once())
            ->method('loadByCart')
            ->with($context, $cart, static::equalTo($behavior))
            ->willReturn($result);

        $calculator = new CartCalculator($cartRuleLoader, new CartContextHasher(new EventDispatcher()));
        $calculatedCart = $calculator->calculate($cart, $context);

        static::assertFalse($calculatedCart->isModified());
        static::assertCount(2, $calculatedCart->getLineItems());

        foreach ($calculatedCart->getLineItems() as $lineItem) {
            static::assertFalse($lineItem->isModified());
        }
    }

    public function testSetHash(): void
    {
        $context = Generator::generateSalesChannelContext();
        $behavior = new CartBehavior($context->getPermissions());
        $cart = $this->getCart();
        $result = new RuleLoaderResult($cart, new RuleCollection());

        $cartRuleLoader = $this->createMock(CartRuleLoader::class);
        $cartRuleLoader
            ->expects(static::once())
            ->method('loadByCart')
            ->with($context, $cart, static::equalTo($behavior))
            ->willReturn($result);

        $calculator = new CartCalculator($cartRuleLoader, new CartContextHasher(new EventDispatcher()));
        $calculatedCart = $calculator->calculate($cart, $context);

        static::assertSame(self::EXPECTED_HASH, $calculatedCart->getHash());
    }

    private function getCart(): Cart
    {
        $cart = new Cart('hatoken');
        $cart->markModified();

        $item1 = new LineItem('a', 'product');
        $item1->markModified();

        $item2 = new LineItem('b', 'product');
        $item2->markModified();

        $cart->add($item1);
        $cart->add($item2);

        return $cart;
    }
}
