<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Price;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\CashRounding;
use Cicada\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\NetPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Cicada\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Cicada\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\Tax\TaxCalculator;
use Cicada\Core\Test\Generator;

/**
 * @internal
 */
#[CoversClass(AbsolutePriceCalculator::class)]
class AbsolutePriceCalculatorTest extends TestCase
{
    #[DataProvider('provider')]
    public function testCalculateAbsolutePriceOfGrossPrices(AbsoluteCalculation $calculation): void
    {
        $rounding = new CashRounding();

        $taxCalculator = new TaxCalculator();

        $calculator = new AbsolutePriceCalculator(
            new QuantityPriceCalculator(
                new GrossPriceCalculator($taxCalculator, $rounding),
                new NetPriceCalculator($taxCalculator, $rounding),
            ),
            new PercentageTaxRuleBuilder()
        );

        $calculatedPrice = $calculator->calculate(
            $calculation->getDiscount(),
            $calculation->getPrices(),
            Generator::createSalesChannelContext()
        );

        static::assertEquals($calculation->getExpected()->getCalculatedTaxes(), $calculatedPrice->getCalculatedTaxes());
        static::assertEquals($calculation->getExpected()->getTaxRules(), $calculatedPrice->getTaxRules());
        static::assertEquals($calculation->getExpected()->getTotalPrice(), $calculatedPrice->getTotalPrice());
        static::assertEquals($calculation->getExpected()->getUnitPrice(), $calculatedPrice->getUnitPrice());
        static::assertEquals($calculation->getExpected()->getQuantity(), $calculatedPrice->getQuantity());
    }

    /**
     * @return array<string, list<AbsoluteCalculation>>
     */
    public static function provider(): array
    {
        return [
            'small-discounts' => [self::getSmallDiscountCase()],
            '100%' => [self::getOneHundredPercentageDiscountCase()],
        ];
    }

    private static function getSmallDiscountCase(): AbsoluteCalculation
    {
        $calculator = self::createQuantityPriceCalculator();

        $definition = new QuantityPriceDefinition(30, new TaxRuleCollection([new TaxRule(19)]));
        $price1 = $calculator->calculate($definition, Generator::createSalesChannelContext());

        $definition = new QuantityPriceDefinition(30, new TaxRuleCollection([new TaxRule(7)]));
        $price2 = $calculator->calculate($definition, Generator::createSalesChannelContext());

        return new AbsoluteCalculation(
            -6,
            new CalculatedPrice(
                -6,
                -6,
                new CalculatedTaxCollection([
                    new CalculatedTax(-0.48, 19, -3),
                    new CalculatedTax(-0.20, 7, -3),
                ]),
                new TaxRuleCollection([
                    new TaxRule(19, 50),
                    new TaxRule(7, 50),
                ]),
                1
            ),
            new PriceCollection([$price1, $price2])
        );
    }

    private static function getOneHundredPercentageDiscountCase(): AbsoluteCalculation
    {
        $calculator = self::createQuantityPriceCalculator();

        $priceDefinition = new QuantityPriceDefinition(29.00, new TaxRuleCollection([new TaxRule(17, 100)]), 10);

        $price = $calculator->calculate($priceDefinition, Generator::createSalesChannelContext());

        return new AbsoluteCalculation(
            -290,
            new CalculatedPrice(
                -290,
                -290,
                new CalculatedTaxCollection([
                    new CalculatedTax(-42.14, 17, -290),
                ]),
                new TaxRuleCollection([new TaxRule(17)])
            ),
            new PriceCollection([$price])
        );
    }

    private static function createQuantityPriceCalculator(): QuantityPriceCalculator
    {
        $rounding = new CashRounding();
        $taxCalculator = new TaxCalculator();

        return new QuantityPriceCalculator(
            new GrossPriceCalculator($taxCalculator, $rounding),
            new NetPriceCalculator($taxCalculator, $rounding),
        );
    }
}

/**
 * @internal
 */
class AbsoluteCalculation
{
    public function __construct(
        protected float $discount,
        protected CalculatedPrice $expected,
        protected PriceCollection $prices
    ) {
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getExpected(): CalculatedPrice
    {
        return $this->expected;
    }

    public function getPrices(): PriceCollection
    {
        return $this->prices;
    }
}
