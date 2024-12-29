<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\LineItem\Group\Helpers\Traits;

use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\ListPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('checkout')]
trait LineItemTestFixtureBehaviour
{
    /**
     * Create a simple product line item with the provided price.
     */
    private function createProductItem(float $netPrice, float $taxRate, ?float $listPriceNet = null): LineItem
    {
        $product = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);

        // allow quantity change
        $product->setStackable(true);

        $taxValue = $netPrice * ($taxRate / 100.0);

        $grossPrice = $netPrice + $taxValue;

        $calculatedTaxes = new CalculatedTaxCollection();
        $calculatedTaxes->add(new CalculatedTax($taxValue, $taxRate, $taxValue));

        $listPrice = null;
        if ($listPriceNet !== null) {
            $listPrice = ListPrice::createFromUnitPrice($netPrice, $listPriceNet);
        }

        $product->setPrice(new CalculatedPrice(
            $grossPrice,
            $grossPrice,
            $calculatedTaxes,
            new TaxRuleCollection(),
            1,
            null,
            $listPrice
        ));

        return $product;
    }
}
