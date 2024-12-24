<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Promotion\Cart\Discount\Calculator;

use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantity;
use Cicada\Core\Checkout\Cart\LineItem\Group\LineItemQuantityCollection;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\LineItem\LineItemFlatCollection;
use Cicada\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\CashRounding;
use Cicada\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\NetPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\Tax\TaxCalculator;
use Cicada\Core\Checkout\Promotion\Cart\Discount\Calculator\DiscountAbsoluteCalculator;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountLineItem;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackage;
use Cicada\Core\Checkout\Promotion\Cart\Discount\DiscountPackageCollection;
use Cicada\Core\Checkout\Promotion\Exception\InvalidPriceDefinitionException;
use Cicada\Core\Checkout\Promotion\PromotionException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\Annotation\DisabledFeatures;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(DiscountAbsoluteCalculator::class)]
class DiscountAbsoluteCalculatorTest extends TestCase
{
    #[DataProvider('priceProvider')]
    public function testCalculate(float $discountIn, float $packageSum, float $discountOut): void
    {
        $context = Generator::createSalesChannelContext();

        $rounding = new CashRounding();

        $taxCalculator = new TaxCalculator();

        $calculator = new AbsolutePriceCalculator(
            new QuantityPriceCalculator(
                new GrossPriceCalculator($taxCalculator, $rounding),
                new NetPriceCalculator($taxCalculator, $rounding),
            ),
            new PercentageTaxRuleBuilder()
        );

        $discountCalculator = new DiscountAbsoluteCalculator($calculator);

        $priceDefinition = new AbsolutePriceDefinition($discountIn);
        $discount = new DiscountLineItem('foo', $priceDefinition, ['discountScope' => 'foo', 'discountType' => 'bar'], null);

        $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, Uuid::randomHex(), 2);
        $lineItem->setPrice(new CalculatedPrice($packageSum / 2, $packageSum, new CalculatedTaxCollection(), new TaxRuleCollection()));

        $package = new DiscountPackage(
            new LineItemQuantityCollection([
                new LineItemQuantity($lineItem->getId(), 1),
                new LineItemQuantity($lineItem->getId(), 1),
            ])
        );
        $package->setCartItems(new LineItemFlatCollection([$lineItem]));

        $price = $discountCalculator->calculate($discount, new DiscountPackageCollection([$package]), $context);

        static::assertEquals($discountOut, $price->getPrice()->getTotalPrice());
    }

    #[DisabledFeatures(['v6.7.0.0'])]
    public function testInvalidPriceDefinitionThrowWithDisabledFeatures(): void
    {
        $context = Generator::createSalesChannelContext();

        $rounding = new CashRounding();

        $taxCalculator = new TaxCalculator();

        $calculator = new AbsolutePriceCalculator(
            new QuantityPriceCalculator(
                new GrossPriceCalculator($taxCalculator, $rounding),
                new NetPriceCalculator($taxCalculator, $rounding),
            ),
            new PercentageTaxRuleBuilder()
        );

        $discountCalculator = new DiscountAbsoluteCalculator($calculator);

        $priceDefinition = new PercentagePriceDefinition(23.5);
        $discount = new DiscountLineItem('foo', $priceDefinition, ['discountScope' => 'foo', 'discountType' => 'bar'], null);

        static::expectException(InvalidPriceDefinitionException::class);

        $discountCalculator->calculate($discount, new DiscountPackageCollection(), $context);
    }

    public function testInvalidPriceDefinitionThrow(): void
    {
        $context = Generator::createSalesChannelContext();

        $rounding = new CashRounding();

        $taxCalculator = new TaxCalculator();

        $calculator = new AbsolutePriceCalculator(
            new QuantityPriceCalculator(
                new GrossPriceCalculator($taxCalculator, $rounding),
                new NetPriceCalculator($taxCalculator, $rounding),
            ),
            new PercentageTaxRuleBuilder()
        );

        $discountCalculator = new DiscountAbsoluteCalculator($calculator);

        $priceDefinition = new PercentagePriceDefinition(23.5);
        $discount = new DiscountLineItem('foo', $priceDefinition, ['discountScope' => 'foo', 'discountType' => 'bar'], null);

        static::expectException(PromotionException::class);

        $discountCalculator->calculate($discount, new DiscountPackageCollection(), $context);
    }

    /**
     * @return iterable<string, float[]>
     */
    public static function priceProvider(): iterable
    {
        yield 'discount greater than packages sum' => [100.00, 90.00, -90.00];
        yield 'discount less than packages sum' => [45.00, 90.00, -45.00];
        yield 'discount equal to packages sum' => [45.00, 45.00, -45.00];
        yield 'discount for zero price product' => [0.0, 0.0, 0.0];
    }
}
