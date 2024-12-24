<?php declare(strict_types=1);

namespace Cicada\Core\Checkout\Promotion\Cart\Discount;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Composition\DiscountCompositionItem;
use Cicada\Core\Framework\Log\Package;

#[Package('buyers-experience')]
class DiscountCalculatorResult
{
    /**
     * @param DiscountCompositionItem[] $compositionItems
     */
    public function __construct(
        private readonly CalculatedPrice $price,
        private readonly array $compositionItems
    ) {
    }

    public function getPrice(): CalculatedPrice
    {
        return $this->price;
    }

    /**
     * @return DiscountCompositionItem[]
     */
    public function getCompositionItems(): array
    {
        return $this->compositionItems;
    }
}
