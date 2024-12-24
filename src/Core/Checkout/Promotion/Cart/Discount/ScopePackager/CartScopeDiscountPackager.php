<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Discount\ScopePackager;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\FilterableInterface;
use Cicada\Core\Checkout\Cart\Rule\LineItemScope;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackage;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackager;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('buyers-experience')]
class CartScopeDiscountPackager extends DiscountPackager
{
    public function getDecorated(): DiscountPackager
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * Gets all product line items of the entire cart that
     * match the rules and conditions of the provided discount item.
     */
    public function getMatchingItems(DiscountLineItem $discount, Cart $cart, SalesChannelContext $context): DiscountPackageCollection
    {
        $allItems = $cart->getLineItems()->filter(fn (LineItem $lineItem) => $lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE && $lineItem->isStackable());

        $priceDefinition = $discount->getPriceDefinition();
        if ($priceDefinition instanceof FilterableInterface && $priceDefinition->getFilter()) {
            $allItems = $allItems->filter(fn (LineItem $lineItem) => $priceDefinition->getFilter()->match(new LineItemScope($lineItem, $context)));
        }

        $discountPackage = $this->getDiscountPackage($allItems);
        if ($discountPackage === null) {
            return new DiscountPackageCollection([]);
        }

        return new DiscountPackageCollection([$discountPackage]);
    }

    private function getDiscountPackage(LineItemCollection $cartItems): ?DiscountPackage
    {
        $discountItems = [];
        foreach ($cartItems as $cartLineItem) {
            for ($i = 1; $i <= $cartLineItem->getQuantity(); ++$i) {
                $item = new LineItemQuantity(
                    $cartLineItem->getId(),
                    1
                );

                $discountItems[] = $item;
            }
        }

        if (\count($discountItems) === 0) {
            return null;
        }

        return new DiscountPackage(
            new LineItemQuantityCollection($discountItems)
        );
    }
}
