<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Hook\Pricing;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Facade\PriceFacade;
use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Checkout\Cart\Price\CashRounding;
use Cicada\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\NetPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Cart\Tax\TaxCalculator;
use Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;
use Cicada\Core\Content\Product\Hook\Pricing\CheapestPriceFacade;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;

/**
 * @internal
 */
#[CoversClass(CheapestPriceFacade::class)]
class CheapestPriceFacadeTest extends TestCase
{
    #[DataProvider('providerChange')]
    public function testChange(string $currencyKey, string $taxState, float $unit, float $tax): void
    {
        $ids = new IdsCollection([
            'default' => Defaults::CURRENCY,
            'usd' => Uuid::randomHex(),
        ]);

        $price = $this->rampUpPriceFacade($ids, $currencyKey, $taxState);

        $update = new PriceCollection([
            new Price(Defaults::CURRENCY, 2, 5, false),
            new Price($ids->get('usd'), 1, 4, false),
        ]);

        $price->change($update);

        static::assertEquals($unit, $price->getUnit());
        static::assertEquals($tax, $price->getTaxes()->getAmount());
    }

    public function testChangeWithPriceFacade(): void
    {
        $ids = new IdsCollection([
            'default' => Defaults::CURRENCY,
            'usd' => Uuid::randomHex(),
        ]);

        $price = $this->rampUpPriceFacade($ids, 'default', CartPrice::TAX_STATE_GROSS);

        $price->change(
            new PriceFacade(
                new Entity(),
                new CalculatedPrice(5, 5, new CalculatedTaxCollection(), new TaxRuleCollection()),
                $this->createMock(ScriptPriceStubs::class),
                $this->createMock(SalesChannelContext::class)
            )
        );

        static::assertEquals(5, $price->getUnit());
    }

    public function testChangeWithNullFacade(): void
    {
        $ids = new IdsCollection([
            'default' => Defaults::CURRENCY,
            'usd' => Uuid::randomHex(),
        ]);

        $price = $this->rampUpPriceFacade($ids, 'default', CartPrice::TAX_STATE_GROSS);

        $price->change(null);

        static::assertEquals(10, $price->getUnit());
    }

    public function testReset(): void
    {
        $ids = new IdsCollection([
            'default' => Defaults::CURRENCY,
            'usd' => Uuid::randomHex(),
        ]);

        $price = $this->rampUpPriceFacade($ids, 'default', CartPrice::TAX_STATE_GROSS);

        $price->reset();

        static::assertEquals(10, $price->getUnit());
    }

    public static function providerChange(): \Generator
    {
        yield 'Test default currency' => ['default', CartPrice::TAX_STATE_GROSS, 5.0, 0.45];
        yield 'Test usd currency' => ['usd', CartPrice::TAX_STATE_GROSS, 4.0, 0.36];

        yield 'Test net default currency' => ['default', CartPrice::TAX_STATE_NET, 2.0, 0.2];
        yield 'Test net usd currency' => ['usd', CartPrice::TAX_STATE_NET, 1.0, 0.1];
    }

    private function rampUpPriceFacade(IdsCollection $ids, string $currencyKey, string $taxState): CheapestPriceFacade
    {
        $entity = new class extends Entity {
            protected CalculatedPrice $calculatedPrice;
        };

        $quantityCalculator = new QuantityPriceCalculator(
            new GrossPriceCalculator(new TaxCalculator(), new CashRounding()),
            new NetPriceCalculator(new TaxCalculator(), new CashRounding())
        );

        $stubs = new ScriptPriceStubs(
            // not necessary for this test
            $this->createMock(Connection::class),
            $quantityCalculator,
            new PercentagePriceCalculator(new CashRounding(), $quantityCalculator, new PercentageTaxRuleBuilder()),
        );

        $entity->assign(['calculatedPrice' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection())]);

        $original = new CalculatedCheapestPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(new TaxRuleCollection([new TaxRule(10)])));

        // mock context to simulate currency and tax states
        $context = $this->createMock(SalesChannelContext::class);

        // currency key will be provided, we want to test different currencies are taking into account
        $context->expects(static::any())->method('getCurrencyId')->willReturn($ids->get($currencyKey));

        // we also want to test different tax states (gross/net)
        $context->expects(static::any())->method('getTaxState')->willReturn($taxState);
        $context->expects(static::any())->method('getItemRounding')->willReturn(new CashRoundingConfig(2, 0.01, true));

        return new CheapestPriceFacade($entity, $original, $stubs, $context);
    }
}
