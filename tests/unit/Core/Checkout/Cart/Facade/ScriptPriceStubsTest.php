<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Facade;

use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Cicada\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ScriptPriceStubs::class)]
class ScriptPriceStubsTest extends TestCase
{
    // fake some static id for the iso
    private const USD_ID = Defaults::LANGUAGE_SYSTEM;

    /**
     * @param array<string, array{gross:float, net:float}> $prices
     */
    #[DataProvider('priceCases')]
    public function testPriceFactory(array $prices, PriceCollection $expected): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllKeyValue')->willReturn([
            'USD' => self::USD_ID,
        ]);

        $stubs = new ScriptPriceStubs($connection, $this->createMock(QuantityPriceCalculator::class), $this->createMock(PercentagePriceCalculator::class));

        $actual = $stubs->build($prices);

        foreach ($expected as $expectedPrice) {
            $actualPrice = $actual->getCurrencyPrice($expectedPrice->getCurrencyId());

            static::assertInstanceOf(Price::class, $actualPrice);
            static::assertEquals($expectedPrice->getNet(), $actualPrice->getNet());
            static::assertEquals($expectedPrice->getGross(), $actualPrice->getGross());
            static::assertEquals($expectedPrice->getLinked(), $actualPrice->getLinked());
        }
    }

    public static function priceCases(): \Generator
    {
        yield 'manual price definition' => [
            [
                'default' => ['gross' => 100, 'net' => 90],
                'USD' => ['gross' => 90, 'net' => 80],
            ],
            new PriceCollection([
                new Price(Defaults::CURRENCY, 90, 100, false),
                new Price(self::USD_ID, 80, 90, false),
            ]),
        ];

        yield 'storage price definition' => [
            [
                ['gross' => 100, 'net' => 90, 'linked' => true, 'currencyId' => Defaults::CURRENCY],
                ['gross' => 90, 'net' => 80, 'linked' => false, 'currencyId' => self::USD_ID],
            ],
            new PriceCollection([
                new Price(Defaults::CURRENCY, 90, 100, true),
                new Price(self::USD_ID, 80, 90, false),
            ]),
        ];
    }
}
