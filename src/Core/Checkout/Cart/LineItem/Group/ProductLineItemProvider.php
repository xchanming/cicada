<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Cart\LineItem\Group;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;

#[Package('checkout')]
class ProductLineItemProvider extends AbstractProductLineItemProvider
{
    public function getDecorated(): AbstractProductLineItemProvider
    {
        throw new DecorationPatternException(self::class);
    }

    public function getProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);
    }
}
