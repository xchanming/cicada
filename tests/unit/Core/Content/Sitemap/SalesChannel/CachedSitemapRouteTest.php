<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Sitemap\SalesChannel;

use Cicada\Core\Content\Sitemap\Event\SitemapRouteCacheKeyEvent;
use Cicada\Core\Content\Sitemap\SalesChannel\AbstractSitemapRoute;
use Cicada\Core\Content\Sitemap\SalesChannel\CachedSitemapRoute;
use Cicada\Core\Content\Sitemap\SalesChannel\SitemapRouteResponse;
use Cicada\Core\Content\Sitemap\Struct\SitemapCollection;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[CoversClass(CachedSitemapRoute::class)]
class CachedSitemapRouteTest extends TestCase
{
    private MockObject&AbstractSitemapRoute $decorated;

    private MockObject&CacheInterface $cache;

    private EventDispatcher $eventDispatcher;

    private CachedSitemapRoute $cachedRoute;

    private SalesChannelContext $context;

    private SitemapRouteResponse $response;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        $this->decorated = $this->createMock(AbstractSitemapRoute::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->eventDispatcher = new EventDispatcher();
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $this->context = Generator::createSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
            salesChannel: $salesChannel
        );
        $this->response = new SitemapRouteResponse(
            new SitemapCollection()
        );

        $this->cachedRoute = new CachedSitemapRoute(
            $this->decorated,
            $this->cache,
            $this->createMock(EntityCacheKeyGenerator::class),
            $this->createMock(AbstractCacheTracer::class),
            $this->eventDispatcher,
            [],
            $this->createMock(SystemConfigService::class)
        );
    }

    public function testLoadWithDisabledCacheWillCallDecoratedRoute(): void
    {
        $this->decorated
            ->expects(static::once())
            ->method('load')
            ->willReturn($this->response);
        $this->cache
            ->expects(static::never())
            ->method('get');
        $this->eventDispatcher->addListener(
            SitemapRouteCacheKeyEvent::class,
            fn (SitemapRouteCacheKeyEvent $event) => $event->disableCaching()
        );

        $this->cachedRoute->load(new Request(), $this->context);
    }

    public function testLoadWithEnabledCacheWillReturnDataFromCache(): void
    {
        $this->decorated
            ->expects(static::never())
            ->method('load');
        $this->cache
            ->expects(static::once())
            ->method('get')
            ->willReturn(CacheValueCompressor::compress($this->response));
        $this->eventDispatcher->addListener(
            SitemapRouteCacheKeyEvent::class,
            fn (SitemapRouteCacheKeyEvent $event) => $event
        );

        $this->cachedRoute->load(new Request(), $this->context);
    }
}
