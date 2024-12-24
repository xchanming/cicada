<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Category\SalesChannel;

use Cicada\Core\Content\Category\Event\CategoryRouteCacheTagsEvent;
use Cicada\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Cicada\Core\Content\Category\SalesChannel\CategoryRoute;
use Cicada\Core\Content\Test\Cms\LayoutBuilder;
use Cicada\Core\Content\Test\Product\ProductBuilder;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Group('cache')]
#[Group('store-api')]
class CachedCategoryRouteTest extends TestCase
{
    use KernelTestBehaviour;

    private const ALL_TAG = 'test-tag';

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        parent::setUp();

        $this->context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    #[BeforeClass]
    public static function startTransactionBefore(): void
    {
        KernelLifecycleManager::getKernel()
            ->getContainer()
            ->get(Connection::class)
            ->beginTransaction();
    }

    #[AfterClass]
    public static function stopTransactionAfter(): void
    {
        KernelLifecycleManager::getKernel()
            ->getContainer()
            ->get(Connection::class)
            ->rollBack();
    }

    #[DataProvider('invalidationProvider')]
    public function testInvalidation(IdsCollection $ids, \Closure $after, int $calls): void
    {
        if (!$ids->has('navigation')) {
            // to improve performance, we generate the required data one time and test different case with same data set
            $this->initData($ids);
        }

        static::getContainer()->get('cache.object')->invalidateTags([self::ALL_TAG]);

        static::getContainer()->get('event_dispatcher')
            ->addListener(CategoryRouteCacheTagsEvent::class, static function (CategoryRouteCacheTagsEvent $event): void {
                $event->addTags([self::ALL_TAG]);
            });

        $route = static::getContainer()->get(CategoryRoute::class);
        static::assertInstanceOf(CachedCategoryRoute::class, $route);

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::exactly($calls))->method('__invoke');

        static::getContainer()
            ->get('event_dispatcher')
            ->addListener(CategoryRouteCacheTagsEvent::class, $listener);

        $context = $this->context;
        $id = $context->getSalesChannel()->getNavigationCategoryId();

        $route->load($id, new Request(), $context);
        $route->load($id, new Request(), $context);

        $after($ids, $context, static::getContainer());

        $route->load($id, new Request(), $context);
        $route->load($id, new Request(), $context);

        static::getContainer()
            ->get('event_dispatcher')
            ->removeListener(CategoryRouteCacheTagsEvent::class, $listener);
    }

    public static function invalidationProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Test call multiple times without change' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context): void {
            },
            1,
        ];

        yield 'Test assign a new product to the category as listing product' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $product = (new ProductBuilder($ids, 'test-assign'))
                    ->price(100)
                    ->visibility()
                    ->category('navigation')
                    ->build();

                $container->get('product.repository')
                    ->create([$product], $context->getContext());
            },
            2,
        ];

        yield 'Test update a product which is assigned as listing product' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $update = [
                    'id' => $ids->get('to-update'),
                    'name' => 'test',
                ];
                $container->get('product.repository')
                    ->update([$update], $context->getContext());
            },
            2,
        ];

        yield 'Test remove a product from listing assignment' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $mapping = [
                    'productId' => $ids->get('slider-remove'),
                    'categoryId' => $ids->get('navigation'),
                ];

                $container->get('product_category.repository')
                    ->delete([$mapping], $context->getContext());
            },
            2,
        ];

        yield 'Test delete a product which is assigned as listing product' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('product.repository')
                    ->delete([['id' => $ids->get('listing-delete')]], $context->getContext());
            },
            2,
        ];

        yield 'Test update the category data' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $update = [
                    'id' => $ids->get('navigation'),
                    'name' => 'update',
                ];
                $container->get('category.repository')->update([$update], $context->getContext());
            },
            2,
        ];

        yield 'Test update the layout data' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $update = [
                    'id' => $ids->get('layout'),
                    'name' => 'update',
                ];
                $container->get('cms_page.repository')->update([$update], $context->getContext());
            },
            2,
        ];

        yield 'Test update a product which is inside a slider element' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $update = [
                    'id' => $ids->get('other-slider-product'),
                    'name' => 'test',
                ];
                $container->get('product.repository')
                    ->update([$update], $context->getContext());
            },
            2,
        ];

        yield 'Test delete a product which is inside a slider element' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('product.repository')
                    ->delete([['id' => $ids->get('slider-delete')]], $context->getContext());
            },
            2,
        ];

        yield 'Test update a product which is inside a box element' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $update = [
                    'id' => $ids->get('other-box-product'),
                    'name' => 'test',
                ];
                $container->get('product.repository')
                    ->update([$update], $context->getContext());
            },
            2,
        ];

        yield 'Test delete a product which is inside a box element' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('product.repository')
                    ->delete([['id' => $ids->get('box-delete')]], $context->getContext());
            },
            2,
        ];

        yield 'Test update a product which is not assigned to an element or the listing' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $update = [
                    'id' => $ids->get('not-assigned'),
                    'name' => 'test',
                ];
                $container->get('product.repository')
                    ->update([$update], $context->getContext());
            },
            1,
        ];

        yield 'Test delete a product which is not assigned to an element or the listing' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('product.repository')
                    ->delete([['id' => $ids->get('not-assigned-delete')]], $context->getContext());
            },
            1,
        ];

        yield 'Test create product included in stream' => [
            $ids,
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $product = (new ProductBuilder($ids, 'in-stream'))
                    ->name('foobar')
                    ->price(100)
                    ->visibility()
                    ->build();

                $container->get('product.repository')
                    ->create([$product], $context->getContext());
            },
            2,
        ];
    }

    private function initData(IdsCollection $ids): void
    {
        $context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $ids->set('navigation', $context->getSalesChannel()->getNavigationCategoryId());

        static::getContainer()->get('product_stream.repository')->create([
            [
                'id' => $ids->get('stream'),
                'filters' => [
                    [
                        'type' => 'equals',
                        'field' => 'name',
                        'value' => 'foobar',
                    ],
                ],
                'name' => 'testStream',
            ],
        ], $context->getContext());

        $products = [
            (new ProductBuilder($ids, 'to-update'))
                ->price(100)
                ->visibility()
                ->category('navigation')
                ->build(),
            (new ProductBuilder($ids, 'listing-delete'))
                ->price(100)
                ->visibility()
                ->category('navigation')
                ->build(),
            (new ProductBuilder($ids, 'not-assigned'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'not-assigned-delete'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'other-slider-product'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'other-box-product'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'slider-delete'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'slider-remove'))
                ->price(100)
                ->visibility()
                ->build(),
            (new ProductBuilder($ids, 'box-delete'))
                ->price(100)
                ->visibility()
                ->build(),
        ];

        static::getContainer()->get('product.repository')->create($products, $context->getContext());

        $this->context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);

        $builder = new LayoutBuilder($ids, 'layout');
        $builder
            ->productSlider(['other-slider-product', 'slider-delete', 'slider-remove'])
            ->listing()
            ->productThreeColumnBlock(['other-box-product', 'box-delete', 'other-box-product'])
            ->productStreamSlider('stream')
        ;

        // generate layout with product boxes, listing and slider
        static::getContainer()->get('cms_page.repository')->create([$builder->build()], $context->getContext());

        $update = [
            'id' => $context->getSalesChannel()->getNavigationCategoryId(),
            'cmsPageId' => $ids->get('layout'),
        ];

        // assign other layout for testing
        static::getContainer()->get('category.repository')
            ->update([$update], $context->getContext());
    }
}
