<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Discount\Calculator;

use Cicada\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Composition\DiscountCompositionItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountCalculatorInterface;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountCalculatorResult;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Cicada\Core\Checkout\Promotion\Exception\InvalidPriceDefinitionException;
use Cicada\Core\Checkout\Promotion\PromotionException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

#[Package('checkout')]
class DiscountAbsoluteCalculator implements DiscountCalculatorInterface
{
    public function __construct(private readonly AbsolutePriceCalculator $priceCalculator)
    {
    }

    /**
     * @throws InvalidPriceDefinitionException
     */
    public function calculate(DiscountLineItem $discount, DiscountPackageCollection $packages, SalesChannelContext $context): DiscountCalculatorResult
    {
        /** @var AbsolutePriceDefinition $definition */
        $definition = $discount->getPriceDefinition();

        if (!$definition instanceof AbsolutePriceDefinition) {
            throw PromotionException::invalidPriceDefinition($discount->getLabel(), $discount->getCode());
        }

        $affectedPrices = $packages->getAffectedPrices();

        $totalOriginalSum = $affectedPrices->sum()->getTotalPrice();
        $discountValue = -min(abs($definition->getPrice()), $totalOriginalSum);

        $price = $this->priceCalculator->calculate(
            $discountValue,
            $affectedPrices,
            $context
        );

        $composition = $this->getCompositionItems(
            $discountValue,
            $packages,
            $totalOriginalSum
        );

        return new DiscountCalculatorResult($price, $composition);
    }

    /**
     * @return DiscountCompositionItem[]
     */
    private function getCompositionItems(float $discountValue, DiscountPackageCollection $packages, float $totalOriginalSum): array
    {
        $items = [];

        foreach ($packages as $package) {
            foreach ($package->getCartItems() as $lineItem) {
                if ($lineItem->getPrice() === null) {
                    continue;
                }

                $itemTotal = $lineItem->getPrice()->getTotalPrice();

                $factor = $totalOriginalSum === 0.0 ? 0 : $itemTotal / $totalOriginalSum;

                $items[] = new DiscountCompositionItem(
                    $lineItem->getId(),
                    $lineItem->getQuantity(),
                    abs($discountValue) * $factor
                );
            }
        }

        return $items;
    }
}
