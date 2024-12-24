<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Category\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\CategoryEntity;
use Cicada\Core\Content\Category\Event\CategoryRouteCacheKeyEvent;
use Cicada\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Cicada\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Cicada\Core\Content\Category\SalesChannel\CategoryRouteResponse;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\Test\Generator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[CoversClass(CachedCategoryRoute::class)]
class CachedCategoryRouteTest extends TestCase
{
    private MockObject&AbstractCategoryRoute $decorated;

    private MockObject&CacheInterface $cache;

    private EventDispatcher $eventDispatcher;

    private CachedCategoryRoute $cachedRoute;

    private SalesChannelContext $context;

    private CategoryRouteResponse $response;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        $this->decorated = $this->createMock(AbstractCategoryRoute::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->eventDispatcher = new EventDispatcher();
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $this->context = Generator::createSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
            salesChannel: $salesChannel
        );
        $this->response = new CategoryRouteResponse(
            new CategoryEntity()
        );

        $this->cachedRoute = new CachedCategoryRoute(
            $this->decorated,
            $this->cache,
            $this->createMock(EntityCacheKeyGenerator::class),
            $this->createMock(AbstractCacheTracer::class),
            $this->eventDispatcher,
            []
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
            CategoryRouteCacheKeyEvent::class,
            fn (CategoryRouteCacheKeyEvent $event) => $event->disableCaching()
        );

        $this->cachedRoute->load('', new Request(), $this->context);
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
            CategoryRouteCacheKeyEvent::class,
            fn (CategoryRouteCacheKeyEvent $event) => $event
        );

        $this->cachedRoute->load('', new Request(), $this->context);
    }
}
