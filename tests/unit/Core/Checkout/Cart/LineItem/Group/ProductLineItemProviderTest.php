<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\Group\AbstractProductLineItemProvider;
use Cicada\Core\Checkout\Cart\LineItem\Group\ProductLineItemProvider;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Traits\LineItemTestFixtureBehaviour;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ProductLineItemProvider::class)]
class ProductLineItemProviderTest extends TestCase
{
    use LineItemTestFixtureBehaviour;

    private AbstractProductLineItemProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductLineItemProvider();
    }

    public function testIsMatchingReturnProductLineItem(): void
    {
        $cart = $this->getCart();

        static::assertEquals(4, $cart->getLineItems()->count());

        $lineItems = $this->provider->getProducts($cart);

        static::assertEquals(1, $lineItems->count());
        static::assertNotNull($lineItems->first());
        static::assertEquals(LineItem::PRODUCT_LINE_ITEM_TYPE, $lineItems->first()->getType());
    }

    public function testItThrowsDecorationPatternException(): void
    {
        $this->expectException(DecorationPatternException::class);

        $this->provider->getDecorated();
    }

    private function getCart(): Cart
    {
        $items = [
            new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE),
            new LineItem(Uuid::randomHex(), LineItem::PROMOTION_LINE_ITEM_TYPE),
            new LineItem(Uuid::randomHex(), LineItem::CREDIT_LINE_ITEM_TYPE),
            new LineItem(Uuid::randomHex(), LineItem::CUSTOM_LINE_ITEM_TYPE),
        ];

        $cart = new Cart('token');
        $cart->addLineItems(new LineItemCollection($items));

        return $cart;
    }
}
