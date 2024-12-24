<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Framework\Seo\SeoUrlRoute;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Category\Event\CategoryIndexerEvent;
use Cicada\Core\Content\Seo\SeoUrlUpdater;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\NavigationPageSeoUrlRoute;
use Cicada\Storefront\Framework\Seo\SeoUrlRoute\SeoUrlUpdateListener;

/**
 * @internal
 */
#[CoversClass(SeoUrlUpdateListener::class)]
class SeoUrlUpdateListenerTest extends TestCase
{
    private SeoUrlUpdater&MockObject $seoUrlUpdater;

    private Connection&MockObject $connection;

    private SeoUrlUpdateListener $listener;

    protected function setUp(): void
    {
        $this->seoUrlUpdater = $this->createMock(SeoUrlUpdater::class);
        $this->connection = $this->createMock(Connection::class);
        $this->listener = new SeoUrlUpdateListener($this->seoUrlUpdater, $this->connection);
    }

    public function testUpdateCategoryUrlsWithFullIndexing(): void
    {
        $childUuid = Uuid::randomHex();
        $event = new CategoryIndexerEvent([$childUuid], Context::createDefaultContext(), [], true);

        $this->connection->expects(static::never())->method('createQueryBuilder');
        $this->seoUrlUpdater->expects(static::once())
            ->method('update')
            ->with(
                NavigationPageSeoUrlRoute::ROUTE_NAME,
                [$childUuid]
            );

        $this->listener->updateCategoryUrls($event);
    }

    public function testUpdateCategoryUrlsWithPartialIndexing(): void
    {
        $childUuid = Uuid::randomHex();
        $parentUuid = Uuid::randomHex();

        $childId1 = Uuid::randomBytes();
        $childId2 = Uuid::randomBytes();

        $event = new CategoryIndexerEvent([$childUuid, $parentUuid], Context::createDefaultContext(), [], false);

        $result = $this->createMock(Result::class);
        $result->method('fetchFirstColumn')->willReturn([$childId1, $childId2]);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('executeQuery')->willReturn($result);
        $this->connection->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->seoUrlUpdater->expects(static::once())
            ->method('update')
            ->with(
                NavigationPageSeoUrlRoute::ROUTE_NAME,
                [$childUuid, $parentUuid, Uuid::fromBytesToHex($childId1), Uuid::fromBytesToHex($childId2)]
            );

        $this->listener->updateCategoryUrls($event);
    }
}
