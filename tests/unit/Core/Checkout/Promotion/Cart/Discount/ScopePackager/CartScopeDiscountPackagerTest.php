<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\Cart\Discount\ScopePackager;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Checkout\Cart\Rule\LineItemRule;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackage;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Cicada\Core\Checkout\Promotion\Cart\Discount\ScopePackager\CartScopeDiscountPackager;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Rule\Rule;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CartScopeDiscountPackager::class)]
class CartScopeDiscountPackagerTest extends TestCase
{
    public function testGetMatchingItems(): void
    {
        $context = Generator::createSalesChannelContext();

        $matchingLineItem = (new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, Uuid::randomHex(), 2))->setStackable(true);
        $cart = new Cart('foo');
        $cart->setLineItems(
            new LineItemCollection([
                $matchingLineItem,
                (new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, Uuid::randomHex()))->setStackable(true),
            ])
        );

        $priceDefinition = new AbsolutePriceDefinition(42, new LineItemRule(Rule::OPERATOR_EQ, [$matchingLineItem->getReferencedId() ?? '']));
        $discount = new DiscountLineItem('foo', $priceDefinition, ['discountScope' => 'foo', 'discountType' => 'bar'], null);

        $packager = new CartScopeDiscountPackager();
        $items = $packager->getMatchingItems($discount, $cart, $context);

        $expected = new DiscountPackageCollection([
            new DiscountPackage(
                new LineItemQuantityCollection([
                    new LineItemQuantity($matchingLineItem->getId(), 1),
                    new LineItemQuantity($matchingLineItem->getId(), 1),
                ])
            ),
        ]);

        static::assertEquals($expected, $items);

        $priceDefinition = new PercentagePriceDefinition(42, new LineItemRule(Rule::OPERATOR_EQ, [Uuid::randomHex()]));
        $discount = new DiscountLineItem('foo', $priceDefinition, ['discountScope' => 'foo', 'discountType' => 'bar'], null);

        $items = $packager->getMatchingItems($discount, $cart, $context);

        static::assertEquals(new DiscountPackageCollection([]), $items);
    }
}
