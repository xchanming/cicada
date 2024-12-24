<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Product\Hook;

use Cicada\Core\Checkout\Cart\Facade\ScriptPriceStubs;
use Cicada\Core\Content\Product\Hook\Pricing\ProductPricingHook;
use Cicada\Core\Content\Product\Hook\Pricing\ProductProxy;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Script\Execution\Script;
use Cicada\Core\Framework\Script\Execution\ScriptExecutor;
use Cicada\Core\Framework\Script\Execution\ScriptLoader;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('inventory')]
#[CoversClass(ProductPricingHook::class)]
class ProductPricingHookTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testScripts(): void
    {
        $ids = new IdsCollection();

        $products = [
            (new ProductBuilder($ids, 'p1'))
                ->visibility()
                ->price(100)
                ->build(),
            (new ProductBuilder($ids, 'p2'))
                ->price(100)
                ->visibility()
                ->prices('rule-A', 50)
                ->prices('rule-A', 30, 'default', null, 10)
                ->prices('rule-A', 15, 'default', null, 20)
                ->build(),
            (new ProductBuilder($ids, 'p3'))
                ->price(100)
                ->visibility()
                ->variant((new ProductBuilder($ids, 'p3.1'))->price(50)->build())
                ->variant((new ProductBuilder($ids, 'p3.2'))->price(40)->build())
                ->build(),
        ];

        static::getContainer()->get('product.repository')->create($products, Context::createDefaultContext());

        $salesChannelContext = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $salesChannelContext->getContext()->setRuleIds([$ids->get('rule-A')]);

        $products = static::getContainer()->get('sales_channel.product.repository')
            ->search(new Criteria($ids->getList(['p1', 'p2', 'p3.1'])), $salesChannelContext);

        $stubs = static::getContainer()->get(ScriptPriceStubs::class);

        $p1 = $products->get($ids->get('p1'));
        $p2 = $products->get($ids->get('p2'));
        $p3 = $products->get($ids->get('p3.1'));

        static::assertInstanceOf(Entity::class, $p1);
        static::assertInstanceOf(Entity::class, $p2);
        static::assertInstanceOf(Entity::class, $p3);

        $proxies = [
            $ids->get('p1') => new ProductProxy($p1, $salesChannelContext, $stubs),
            $ids->get('p2') => new ProductProxy($p2, $salesChannelContext, $stubs),
            $ids->get('p3.1') => new ProductProxy($p3, $salesChannelContext, $stubs),
        ];

        $salesChannelContext->considerInheritance();
        $hook = new ProductPricingHookExtension($proxies, $salesChannelContext, $ids);

        // allows easy debugging
        $traces = new ScriptTraces();

        $loader = $this->createMock(ScriptLoader::class);
        $loader->method('get')->willReturn([
            new Script('foo', (string) \file_get_contents(__DIR__ . '/_fixtures/pricing-cases/product-pricing.twig'), new \DateTimeImmutable()),
        ]);

        $executor = new ScriptExecutor($loader, $traces, static::getContainer(), static::getContainer()->get('twig.extension.trans'), 'v6.5.0.0');

        $executor->execute($hook);

        static::assertNotEmpty($traces->getTraces());
        static::assertArrayHasKey('product-pricing', $traces->getTraces());
        static::assertEquals(
            [
                'original' => 100.0,
                'changed' => 1.5,
                'plus' => 3.0,
                'minus' => 1.5,
                'discount' => 1.35,
                'surcharge' => 1.49,
                'price-20' => 15.0,
                'price-30' => 10.0,
                'price-31' => 5.0,
                'name' => 'p2',
                'cheapest' => 40.0,
                'cheapest.change' => 15.0,
                'cheapest.reset' => 50.0,
                'cheapest.discount' => 45.0,
                'cheapest.surcharge' => 49.5,
                'cheapest.minus' => 48.5,
                'cheapest.plus' => 49.5,
            ],
            $traces->getOutput('product-pricing', 0)
        );
    }
}

/**
 * @internal
 *
 * @phpstan-ignore-next-line
 */
class ProductPricingHookExtension extends ProductPricingHook
{
    /**
     * @param ProductProxy[] $products
     */
    public function __construct(
        array $products,
        SalesChannelContext $salesChannelContext,
        public readonly IdsCollection $ids
    ) {
        parent::__construct($products, $salesChannelContext);
    }
}
