<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\Hook\Pricing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Facade\PriceFactoryFactory;
use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Content\Product\Hook\Pricing\ProductPricingHook;
use Cicada\Core\Content\Product\Hook\Pricing\ProductProxy;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\RepositoryFacadeHookFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Facade\SalesChannelRepositoryFacadeHookFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\Facade\SystemConfigFacadeHookFactory;

/**
 * @internal
 */
#[Package('inventory')]
#[CoversClass(ProductPricingHook::class)]
class ProductPricingHookTest extends TestCase
{
    public function testGetProducts(): void
    {
        $salesChannelContext = static::createMock(SalesChannelContext::class);

        $productProxy = new ProductProxy(
            (new SalesChannelProductEntity())->assign(['name' => 'foo']),
            $salesChannelContext,
            $this->createMock(ScriptPriceStubs::class)
        );
        $productPricingHook = new ProductPricingHook([$productProxy], $salesChannelContext);

        static::assertEquals([$productProxy], $productPricingHook->getProducts());
    }

    public function testGetServiceIds(): void
    {
        static::assertEquals(
            [
                RepositoryFacadeHookFactory::class,
                PriceFactoryFactory::class,
                SystemConfigFacadeHookFactory::class,
                SalesChannelRepositoryFacadeHookFactory::class,
            ],
            ProductPricingHook::getServiceIds()
        );
    }

    public function testGetName(): void
    {
        $productPricingHook = new ProductPricingHook([], static::createMock(SalesChannelContext::class));

        static::assertEquals('product-pricing', $productPricingHook->getName());
    }

    public function testGetSalesChannelContext(): void
    {
        $salesChannelContext = static::createMock(SalesChannelContext::class);
        $productPricingHook = new ProductPricingHook([], $salesChannelContext);

        static::assertEquals($salesChannelContext, $productPricingHook->getSalesChannelContext());
    }
}
