<?php
declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Review;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Aggregate\ProductReview\ProductReviewCollection;
use Cicada\Core\Content\Product\SalesChannel\Review\AbstractProductReviewRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\CachedProductReviewRoute;
use Cicada\Core\Content\Product\SalesChannel\Review\ProductReviewRouteResponse;
use Cicada\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Cicada\Core\Framework\Adapter\Cache\CacheValueCompressor;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[Package('inventory')]
#[CoversClass(CachedProductReviewRoute::class)]
class CachedProductReviewRouteTest extends TestCase
{
    private MockObject&AbstractProductReviewRoute $productReviewRoute;

    private MockObject&CacheInterface $cache;

    private CachedProductReviewRoute $route;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        $this->productReviewRoute = $this->createMock(AbstractProductReviewRoute::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->route = new CachedProductReviewRoute(
            $this->productReviewRoute,
            $this->cache,
            new EntityCacheKeyGenerator(),
            $this->createMock(AbstractCacheTracer::class),
            new EventDispatcher(),
            []
        );
    }

    public function testGetDecorated(): void
    {
        static::assertEquals($this->productReviewRoute, $this->route->getDecorated());
    }

    public function testLoadWithSalesChannelContextHasState(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->expects(static::once())->method('hasState')->willReturn(true);

        $this->productReviewRoute
            ->expects(static::once())
            ->method('load')
            ->with('product-id', new Request(), $context, new Criteria());

        $this->route->load('product-id', new Request(), $context, new Criteria());
    }

    public function testLoadWithSalesChannelContextDoesNotHaveState(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->expects(static::once())->method('hasState')->willReturn(false);

        $response = new ProductReviewRouteResponse(
            new EntitySearchResult(
                'product',
                0,
                new ProductReviewCollection(),
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );
        $this->cache
            ->expects(static::once())
            ->method('get')
            ->willReturn(CacheValueCompressor::compress($response));

        $this->route->load('product-id', new Request(), $context, new Criteria());
    }
}
