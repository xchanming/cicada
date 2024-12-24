<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Discount\Calculator;

use Cicada\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Composition\DiscountCompositionItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountCalculatorResult;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Cicada\Core\Checkout\Promotion\Exception\InvalidPriceDefinitionException;
use Cicada\Core\Checkout\Promotion\PromotionException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('buyers-experience')]
class DiscountFixedUnitPriceCalculator
{
    public function __construct(private readonly AbsolutePriceCalculator $absolutePriceCalculator)
    {
    }

    /**
     * @throws InvalidPriceDefinitionException
     */
    public function calculate(
        DiscountLineItem $discount,
        DiscountPackageCollection $packages,
        SalesChannelContext $context
    ): DiscountCalculatorResult {
        $priceDefinition = $discount->getPriceDefinition();

        if (!$priceDefinition instanceof AbsolutePriceDefinition) {
            throw PromotionException::invalidPriceDefinition($discount->getLabel(), $discount->getCode());
        }

        $fixedUnitPrice = abs($priceDefinition->getPrice());

        $totalDiscountSum = 0.0;

        $composition = [];

        foreach ($packages as $package) {
            foreach ($package->getCartItems() as $lineItem) {
                if ($lineItem->getPrice() === null) {
                    continue;
                }

                $quantity = $lineItem->getQuantity();
                $itemUnitPrice = $lineItem->getPrice()->getUnitPrice();

                if ($itemUnitPrice > $fixedUnitPrice) {
                    // check if discount exceeds or not, beware of quantity
                    $discountDiffPrice = ($itemUnitPrice - $fixedUnitPrice) * $quantity;
                    // add to our total discount sum
                    $totalDiscountSum += $discountDiffPrice;

                    // add a reference, so we know what items are discounted
                    $composition[] = new DiscountCompositionItem($lineItem->getId(), $quantity, $discountDiffPrice);
                }
            }
        }

        // now calculate the correct price
        // from our collected total discount price
        $discountPrice = $this->absolutePriceCalculator->calculate(
            -abs($totalDiscountSum),
            $packages->getAffectedPrices(),
            $context
        );

        return new DiscountCalculatorResult($discountPrice, $composition);
    }
}
