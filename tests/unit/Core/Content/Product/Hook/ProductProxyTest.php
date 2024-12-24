<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Hook;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Facade\PriceFacade;
use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Content\Product\DataAbstractionLayer\CheapestPrice\CalculatedCheapestPrice;
use Cicada\Core\Content\Product\Hook\Pricing\PriceCollectionFacade;
use Cicada\Core\Content\Product\Hook\Pricing\ProductProxy;
use Cicada\Core\Content\Product\ProductException;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 */
#[CoversClass(ProductProxy::class)]
class ProductProxyTest extends TestCase
{
    public function testProxyPropertyAccess(): void
    {
        $product = new SalesChannelProductEntity();

        $product->setName('foo');
        $product->setStock(10);

        $product->setCalculatedPrice(
            new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection())
        );

        $product->setCalculatedCheapestPrice(
            new CalculatedCheapestPrice(8, 8, new CalculatedTaxCollection(), new TaxRuleCollection())
        );

        $product->setCalculatedPrices(new PriceCollection([
            new CalculatedPrice(9, 9, new CalculatedTaxCollection(), new TaxRuleCollection()),
        ]));

        $context = $this->createMock(SalesChannelContext::class);

        $stubs = $this->createMock(ScriptPriceStubs::class);

        $proxy = new ProductProxy($product, $context, $stubs);

        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        static::assertInstanceOf(PriceFacade::class, $proxy->calculatedPrice, 'Proxy should return a facade for the calculated price');
        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        static::assertInstanceOf(PriceCollectionFacade::class, $proxy->calculatedPrices, 'Proxy should return a facade for the calculated prices');
        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        static::assertInstanceOf(PriceFacade::class, $proxy->calculatedCheapestPrice, 'Proxy should return a facade for the calculated cheapest price');
        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        static::assertEquals('foo', $proxy->name, 'Proxy should return the same value as the original object');

        static::assertArrayHasKey('stock', $proxy, 'Proxy should be able to check if a property exists');
    }

    public function testUnsetNotAllowed(): void
    {
        $proxy = new ProductProxy(
            (new SalesChannelProductEntity())->assign(['name' => 'foo']),
            $this->createMock(SalesChannelContext::class),
            $this->createMock(ScriptPriceStubs::class)
        );

        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        static::assertEquals('foo', $proxy->name, 'Proxy should return the same value as the original object');

        static::expectException(ProductException::class);
        static::expectExceptionMessage('Manipulation of pricing proxy field name is not allowed');

        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access
        unset($proxy['name']);
    }

    public function testSetNotAllowed(): void
    {
        $proxy = new ProductProxy(
            (new SalesChannelProductEntity())->assign(['name' => 'foo']),
            $this->createMock(SalesChannelContext::class),
            $this->createMock(ScriptPriceStubs::class)
        );

        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        static::assertEquals('foo', $proxy->name, 'Proxy should return the same value as the original object');

        static::expectException(ProductException::class);
        static::expectExceptionMessage('Manipulation of pricing proxy field name is not allowed');

        // @phpstan-ignore-next-line > Access to an undefined property occurs here but the proxy by pass the access to the entity.get() function
        $proxy->name = 'bar';
    }
}
