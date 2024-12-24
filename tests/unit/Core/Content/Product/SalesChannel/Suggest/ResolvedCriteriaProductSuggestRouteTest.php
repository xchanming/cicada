<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Content\Product\SalesChannel\Suggest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Content\Product\Events\ProductSuggestCriteriaEvent;
use Cicada\Core\Content\Product\Events\ProductSuggestResultEvent;
use Cicada\Core\Content\Product\ProductCollection;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter\ManufacturerListingFilterHandler;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter\PriceListingFilterHandler;
use Cicada\Core\Content\Product\SalesChannel\Listing\Filter\ShippingFreeListingFilterHandler;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\AggregationListingProcessor;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\BehaviorListingProcessor;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\CompositeListingProcessor;
use Cicada\Core\Content\Product\SalesChannel\Listing\Processor\PagingListingProcessor;
use Cicada\Core\Content\Product\SalesChannel\Listing\ProductListingResult;
use Cicada\Core\Content\Product\SalesChannel\Suggest\AbstractProductSuggestRoute;
use Cicada\Core\Content\Product\SalesChannel\Suggest\ProductSuggestRouteResponse;
use Cicada\Core\Content\Product\SalesChannel\Suggest\ResolvedCriteriaProductSuggestRoute;
use Cicada\Core\Content\Product\SearchKeyword\ProductSearchBuilderInterface;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Plugin\Exception\DecorationPatternException;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\SystemConfigService\StaticSystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ResolvedCriteriaProductSuggestRoute::class)]
class ResolvedCriteriaProductSuggestRouteTest extends TestCase
{
    /**
     * @param array<string, mixed> $query
     * @param array<string> $expected
     */
    #[DataProvider('loadProvider')]
    public function testRequestHandling(array $query, array $expected): void
    {
        $decorated = new SuggestRouteStub();

        $route = new ResolvedCriteriaProductSuggestRoute(
            $this->createMock(ProductSearchBuilderInterface::class),
            new EventDispatcher(),
            $decorated,
            new CompositeListingProcessor([
                new PagingListingProcessor(new StaticSystemConfigService()),
                new AggregationListingProcessor(
                    [
                        new ManufacturerListingFilterHandler(),
                        new PriceListingFilterHandler(),
                        new ShippingFreeListingFilterHandler(),
                    ],
                    new EventDispatcher()
                ),
                new BehaviorListingProcessor(),
            ])
        );

        $request = new Request(array_merge(['search' => 'foo'], $query));
        $route->load($request, $this->createMock(SalesChannelContext::class), new Criteria());

        static::assertInstanceOf(Criteria::class, $decorated->criteria);
        $fields = $decorated->criteria->getFilterFields();

        static::assertEquals($expected, $fields);
    }

    public function testEvents(): void
    {
        $request = new Request();
        $request->query->set('search', 'test');

        $criteria = new Criteria();

        $builder = $this->createMock(ProductSearchBuilderInterface::class);
        $builder->expects(static::once())->method('build');

        $dispatcher = new EventDispatcher();
        $listener = $this->createMock(CallableClass::class);
        $listener->expects(static::exactly(1))->method('__invoke');
        $dispatcher->addListener(ProductSuggestCriteriaEvent::class, $listener);

        $resultListener = $this->createMock(CallableClass::class);
        $resultListener->expects(static::exactly(1))->method('__invoke');
        $dispatcher->addListener(ProductSuggestResultEvent::class, $resultListener);

        $context = $this->createMock(SalesChannelContext::class);

        $route = new ResolvedCriteriaProductSuggestRoute(
            $builder,
            $dispatcher,
            $this->createMock(AbstractProductSuggestRoute::class),
            new CompositeListingProcessor([])
        );

        $route->load($request, $context, $criteria);
    }

    public static function loadProvider(): \Generator
    {
        yield 'Test with empty request' => [
            [],
            [
                'product.visibilities.visibility',
                'product.visibilities.salesChannelId',
                'product.active',
            ],
        ];

        yield 'Test with manufacturer filter' => [
            ['manufacturer' => 'foo'],
            [
                'product.visibilities.visibility',
                'product.visibilities.salesChannelId',
                'product.active',
                'product.manufacturerId',
            ],
        ];

        yield 'Test with min price filter' => [
            ['min-price' => 100],
            [
                'product.visibilities.visibility',
                'product.visibilities.salesChannelId',
                'product.active',
                'product.cheapestPrice',
            ],
        ];

        yield 'Test with max price filter' => [
            ['max-price' => 100],
            [
                'product.visibilities.visibility',
                'product.visibilities.salesChannelId',
                'product.active',
                'product.cheapestPrice',
            ],
        ];

        yield 'Test with shipping free filter' => [
            ['shipping-free' => true],
            [
                'product.visibilities.visibility',
                'product.visibilities.salesChannelId',
                'product.active',
                'product.shippingFree',
            ],
        ];
    }
}

/**
 * @internal
 */
class SuggestRouteStub extends AbstractProductSuggestRoute
{
    public ?Criteria $criteria = null;

    public function getDecorated(): AbstractProductSuggestRoute
    {
        throw new DecorationPatternException(self::class);
    }

    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): ProductSuggestRouteResponse
    {
        $this->criteria = $criteria;

        return new ProductSuggestRouteResponse(
            new ProductListingResult('product', 0, new ProductCollection(), null, $criteria, Context::createDefaultContext())
        );
    }
}
