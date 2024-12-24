<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Suggest;

use Cicada\Core\Content\Product\Events\ProductSuggestRouteCacheKeyEvent;
use Cicada\Core\Content\Product\ProductEntity;
use Cicada\Core\Content\Product\SalesChannel\Suggest\AbstractProductSuggestRoute;
use Cicada\Core\Content\Product\SalesChannel\Suggest\CachedProductSuggestRoute;
use Cicada\Core\Content\Product\SalesChannel\Suggest\ProductSuggestRouteResponse;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
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
#[CoversClass(CachedProductSuggestRoute::class)]
class CachedProductSuggestRouteTest extends TestCase
{
    private MockObject&AbstractProductSuggestRoute $decorated;

    private MockObject&CacheInterface $cache;

    private EventDispatcher $eventDispatcher;

    private CachedProductSuggestRoute $cachedRout;

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        $this->decorated = $this->createMock(AbstractProductSuggestRoute::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->eventDispatcher = new EventDispatcher();
        $this->context = Generator::createSalesChannelContext(
            baseContext: new Context(new SalesChannelApiSource(Uuid::randomHex())),
        );

        $this->cachedRout = new CachedProductSuggestRoute(
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
            ->willReturn($this->createMock(ProductSuggestRouteResponse::class));
        $this->cache
            ->expects(static::never())
            ->method('get');
        $this->eventDispatcher->addListener(
            ProductSuggestRouteCacheKeyEvent::class,
            fn (ProductSuggestRouteCacheKeyEvent $event) => $event->disableCaching()
        );

        $this->cachedRout->load(new Request(), $this->context, new Criteria());
    }

    public function testLoadWithEnabledCacheWillReturnDataFromCache(): void
    {
        $this->decorated
            ->expects(static::never())
            ->method('load');
        $this->cache
            ->expects(static::once())
            ->method('get')
            ->willReturn(
                CacheValueCompressor::compress(
                    new ProductSuggestRouteResponse(new ProductEntity())
                )
            );
        $this->eventDispatcher->addListener(
            ProductSuggestRouteCacheKeyEvent::class,
            fn (ProductSuggestRouteCacheKeyEvent $event) => $event
        );

        $this->cachedRout->load(new Request(), $this->context, new Criteria());
    }
}
