<?php
declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Detail;

use Cicada\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Cicada\Core\Content\Cms\SalesChannel\SalesChannelCmsPageLoader;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Content\Product\Exception\ProductNotFoundException;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\AbstractProductCloseoutFilterFactory;
use Cicada\Core\Content\Product\SalesChannel\Detail\Event\ResolveVariantIdEvent;
use Cicada\Core\Content\Product\SalesChannel\Detail\ProductConfiguratorLoader;
use Cicada\Core\Content\Product\SalesChannel\Detail\ProductDetailRoute;
use Cicada\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Cicada\Core\Content\Product\SalesChannel\ProductCloseoutFilterFactory;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Cicada\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ProductDetailRoute::class)]
class ProductDetailRouteTest extends TestCase
{
    /**
     * @var MockObject&SalesChannelRepository
     */
    private SalesChannelRepository $productRepository;

    /**
     * @var MockObject&SystemConfigService
     */
    private SystemConfigService $systemConfig;

    private MockObject&Connection $connection;

    private ProductDetailRoute $route;

    private SalesChannelContext $context;

    private IdsCollection $idsCollection;

    private AbstractProductCloseoutFilterFactory $productCloseoutFilterFactory;

    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = Generator::createSalesChannelContext();
        $this->idsCollection = new IdsCollection();
        $this->productRepository = $this->createMock(SalesChannelRepository::class);
        $this->systemConfig = $this->createMock(SystemConfigService::class);
        $this->connection = $this->createMock(Connection::class);
        $configuratorLoader = $this->createMock(ProductConfiguratorLoader::class);
        $breadcrumbBuilder = $this->createMock(CategoryBreadcrumbBuilder::class);
        $cmsPageLoader = $this->createMock(SalesChannelCmsPageLoader::class);
        $this->productCloseoutFilterFactory = new ProductCloseoutFilterFactory();
        $this->eventDispatcher = new EventDispatcher();

        $this->route = new ProductDetailRoute(
            $this->productRepository,
            $this->systemConfig,
            $this->connection,
            $configuratorLoader,
            $breadcrumbBuilder,
            $cmsPageLoader,
            new SalesChannelProductDefinition(),
            $this->productCloseoutFilterFactory,
            $this->eventDispatcher
        );
    }

    public function testLoadMainVariant(): void
    {
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setCmsPageId('4');
        $productEntity->setUniqueIdentifier('mainVariant');
        $this->productRepository->expects(static::exactly(1))
            ->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'product',
                    1,
                    new ProductCollection([$productEntity]),
                    null,
                    new Criteria(),
                    $this->context->getContext()
                )
            );

        $result = $this->route->load('1', new Request(), $this->context, new Criteria());

        static::assertEquals('4', $result->getProduct()->getCmsPageId());
        static::assertEquals('mainVariant', $result->getProduct()->getUniqueIdentifier());
    }

    public function testLoadBestVariant(): void
    {
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setCmsPageId('4');
        $productEntity->setId($this->idsCollection->create('product1'));
        $productEntity->setAvailable(true);
        $productEntity->setUniqueIdentifier('BestVariant');

        $idsSearchResult = new IdSearchResult(
            1,
            [
                [
                    'primaryKey' => $this->idsCollection->get('product1'),
                    'data' => [],
                ],
            ],
            new Criteria(),
            $this->context->getContext()
        );
        $this->productRepository->method('searchIds')
            ->willReturn(
                $idsSearchResult
            );
        $this->productRepository->expects(static::once())
            ->method('search')
            ->willReturnOnConsecutiveCalls(
                new EntitySearchResult('product', 4, new ProductCollection([$productEntity]), null, new Criteria(), $this->context->getContext())
            );

        $result = $this->route->load($this->idsCollection->get('product1'), new Request(), $this->context, new Criteria());

        static::assertEquals(4, $result->getProduct()->getCmsPageId());
        static::assertEquals('BestVariant', $result->getProduct()->getUniqueIdentifier());
        static::assertTrue($result->getProduct()->getAvailable());
    }

    public function testLoadVariantListingConfig(): void
    {
        $this->connection
            ->expects(static::once())
            ->method('fetchAssociative')
            ->willReturn([
                'variantListingConfig' => '{"displayParent": false, "mainVariantId": "2"}',
                'parentId' => '2',
            ]);

        $productId = Uuid::randomHex();
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId($productId);
        $productEntity->setCmsPageId('4');
        $productEntity->setUniqueIdentifier('2');
        $productEntity->setAvailable(true);
        $this->productRepository->expects(static::once())
            ->method('search')
            ->willReturn(
                new EntitySearchResult(
                    'product',
                    1,
                    new ProductCollection([$productEntity]),
                    null,
                    new Criteria(),
                    $this->context->getContext()
                )
            );

        $this->eventDispatcher->addListener(ResolveVariantIdEvent::class, function (ResolveVariantIdEvent $event) use ($productId): void {
            static::assertSame($productId, $event->getProductId());
            static::assertSame('2', $event->getResolvedVariantId());
        });

        $result = $this->route->load($productId, new Request(), $this->context, new Criteria());

        static::assertEquals('2', $result->getProduct()->getUniqueIdentifier());
        static::assertTrue($result->getProduct()->getAvailable());
    }

    public function testResolveVariantIdFromEvent(): void
    {
        $this->connection
            ->expects(static::once())
            ->method('fetchAssociative')
            ->willReturn([
                'variantListingConfig' => '{"displayParent": true, "mainVariantId": "2"}',
                'parentId' => '2',
            ]);

        $variantId = Uuid::randomHex();
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId($variantId);
        $productEntity->setCmsPageId('4');
        $productEntity->setAvailable(true);
        $this->productRepository->expects(static::once())
            ->method('search')
            ->with(static::callback(function (Criteria $criteria) use ($variantId): bool {
                $ids = $criteria->getIds();
                static::assertCount(1, $ids);
                static::assertEquals($variantId, reset($ids));

                return true;
            }))
            ->willReturn(
                new EntitySearchResult(
                    'product',
                    1,
                    new ProductCollection([$productEntity]),
                    null,
                    new Criteria(),
                    $this->context->getContext()
                )
            );

        $this->eventDispatcher->addListener(ResolveVariantIdEvent::class, function (ResolveVariantIdEvent $event) use ($variantId): void {
            $event->setResolvedVariantId($variantId);
        });

        $result = $this->route->load(Uuid::randomHex(), new Request(), $this->context, new Criteria());

        static::assertEquals($variantId, $result->getProduct()->getUniqueIdentifier());
        static::assertTrue($result->getProduct()->getAvailable());
    }

    public function testConfigHideCloseoutProductsWhenOutOfStockFiltersResults(): void
    {
        $productEntity = new SalesChannelProductEntity();
        $productEntity->setId(Uuid::randomHex());
        $productEntity->setCmsPageId('4');
        $productEntity->setUniqueIdentifier('BestVariant');

        $criteria2 = new Criteria([$this->idsCollection->get('product2')]);
        $criteria2->setTitle('product-detail-route');
        $criteria2->addFilter(
            new ProductAvailableFilter('', ProductVisibilityDefinition::VISIBILITY_LINK)
        );

        $filter = $this->productCloseoutFilterFactory->create($this->context);
        $filter->addQuery(new EqualsFilter('product.parentId', null));
        $criteria2->addFilter($filter);

        $this->productRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnOnConsecutiveCalls(
                new EntitySearchResult('product', 4, new ProductCollection([$productEntity]), null, new Criteria(), $this->context->getContext())
            );

        $this->systemConfig->method('get')->willReturn(true);

        $result = $this->route->load($this->idsCollection->get('product2'), new Request(), $this->context, new Criteria());

        static::assertEquals('4', $result->getProduct()->getCmsPageId());
        static::assertEquals('BestVariant', $result->getProduct()->getUniqueIdentifier());
    }

    public function testLoadProductNotFound(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->route->load('1', new Request(), $this->context, new Criteria());
    }

    public function testGetDecorated(): void
    {
        $this->expectException(DecorationPatternException::class);
        $this->route->getDecorated();
    }
}
