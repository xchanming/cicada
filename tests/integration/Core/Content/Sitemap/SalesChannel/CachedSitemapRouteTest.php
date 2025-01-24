<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Sitemap\SalesChannel;

use Cicada\Core\Content\Sitemap\SalesChannel\AbstractSitemapRoute;
use Cicada\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute;
use Cicada\Core\Content\Sitemap\SalesChannel\SitemapRouteResponse;
use Cicada\Core\Content\Sitemap\Service\SitemapExporter;
use Cicada\Core\Content\Sitemap\Service\SitemapExporterInterface;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Adapter\Cache\CacheTracer;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\ProductPageSeoUrlRoute;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('discovery')]
#[Group('cache')]
#[Group('store-api')]
class CachedSitemapRouteTest extends TestCase
{
    use DatabaseTransactionBehaviour;

    use KernelTestBehaviour;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        if (!static::getContainer()->has(ProductPageSeoUrlRoute::class)) {
            static::markTestSkipped('NEXT-16799: Sitemap module has a dependency on storefront routes');
        }
        parent::setUp();
    }

    #[AfterClass]
    public function cleanup(): void
    {
        static::getContainer()->get('cache.object')
            ->invalidateTags([CachedSitemapRoute::ALL_TAG]);
    }

    #[DataProvider('invalidationProvider')]
    public function testInvalidation(\Closure $before, \Closure $after, int $calls, int $strategy = SitemapExporterInterface::STRATEGY_SCHEDULED_TASK): void
    {
        static::getContainer()->get('cache.object')
            ->invalidateTags([CachedSitemapRoute::ALL_TAG]);

        $ids = new IdsCollection();

        $snippetSetId = static::getContainer()->get(Connection::class)
            ->fetchOne('SELECT LOWER(HEX(id)) FROM snippet_set LIMIT 1');

        $domain = [
            'url' => 'http://cicada.test',
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'languageId' => Defaults::LANGUAGE_SYSTEM,
            'currencyId' => Defaults::CURRENCY,
            'snippetSetId' => $snippetSetId,
        ];

        static::getContainer()->get('sales_channel_domain.repository')
            ->create([$domain], Context::createDefaultContext());

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $products = [
            (new ProductBuilder($ids, 'first'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'second'))
                ->price(100)
                ->visibility()
                ->build(),
        ];

        static::getContainer()->get('product.repository')
            ->create($products, Context::createDefaultContext());

        $counter = new SitemapRouteCounter(
            static::getContainer()->get('Cicada\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute.inner')
        );

        $config = $this->createMock(SystemConfigService::class);
        $config->expects(static::any())
            ->method('getInt')
            ->with('core.sitemap.sitemapRefreshStrategy')
            ->willReturn($strategy);

        $route = new CachedSitemapRoute(
            $counter,
            static::getContainer()->get('cache.object'),
            static::getContainer()->get(EntityCacheKeyGenerator::class),
            static::getContainer()->get(CacheTracer::class),
            static::getContainer()->get('event_dispatcher'),
            [],
            $config
        );

        $before($this->context, static::getContainer());

        $route->load(new Request(), $this->context);
        $route->load(new Request(), $this->context);

        $after($this->context, static::getContainer());

        $route->load(new Request(), $this->context);
        $route->load(new Request(), $this->context);

        static::assertSame($calls, $counter->count);
    }

    public static function invalidationProvider(): \Generator
    {
        yield 'Cache invalidated if sitemap generated' => [
            function (): void {
            },
            function (SalesChannelContext $context, ContainerInterface $container): void {
                $container->get(SitemapExporter::class)->generate($context, true);
            },
            2,
        ];

        yield 'Sitemap not cached for live strategy' => [
            function (): void {
            },
            function (): void {
            },
            4,
            SitemapExporterInterface::STRATEGY_LIVE,
        ];
    }
}

/**
 * @internal
 */
class SitemapRouteCounter extends AbstractSitemapRoute
{
    public int $count = 0;

    public function __construct(private readonly AbstractSitemapRoute $decorated)
    {
    }

    public function load(Request $request, SalesChannelContext $context): SitemapRouteResponse
    {
        ++$this->count;

        return $this->getDecorated()->load($request, $context);
    }

    public function getDecorated(): AbstractSitemapRoute
    {
        return $this->decorated;
    }
}
