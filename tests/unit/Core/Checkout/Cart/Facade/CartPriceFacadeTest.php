<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Facade;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Facade\CartPriceFacade;
use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\PriceCollection;

/**
 * @internal
 */
#[CoversClass(CartPriceFacade::class)]
class CartPriceFacadeTest extends TestCase
{
    public function testPublicApiAvailable(): void
    {
        $original = new CartPrice(
            100,
            200,
            300,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS,
            99.99
        );

        $stubs = $this->createMock(ScriptPriceStubs::class);
        $price = new PriceCollection([]);

        $stubs->method('build')->willReturn($price);

        $facade = new CartPriceFacade($original, $stubs);

        static::assertEquals(100, $facade->getNet());
        static::assertEquals(200, $facade->getTotal());
        static::assertEquals(200, $facade->getRounded());
        static::assertEquals(300, $facade->getPosition());
        static::assertEquals(99.99, $facade->getRaw());

        static::assertSame($price, $facade->create([]));
    }
}
