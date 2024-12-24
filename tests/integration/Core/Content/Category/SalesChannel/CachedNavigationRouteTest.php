<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Content\Category\SalesChannel;

use Cicada\Core\Content\Category\Event\NavigationRouteCacheTagsEvent;
use Cicada\Core\Content\Category\SalesChannel\NavigationRoute;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
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
class CachedNavigationRouteTest extends TestCase
{
    use IntegrationTestBehaviour;

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

    #[DataProvider('invalidationProvider')]
    public function testInvalidation(IdsCollection $ids, int $depth, \Closure $before, \Closure $after, int $calls): void
    {
        // to improve performance, we generate the required data one time and test different case with same data set
        $this->initData($ids);

        static::getContainer()->get('cache.object')->invalidateTags([self::ALL_TAG]);

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            NavigationRouteCacheTagsEvent::class,
            static function (NavigationRouteCacheTagsEvent $event): void {
                $event->addTags([self::ALL_TAG]);
            }
        );

        $route = static::getContainer()->get(NavigationRoute::class);

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::exactly($calls))->method('__invoke');

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            NavigationRouteCacheTagsEvent::class,
            $listener
        );

        $context = $this->context;
        $root = $context->getSalesChannel()->getNavigationCategoryId();

        $id = $before($ids, $context, static::getContainer());

        $route->load($id, $root, self::request($depth), $context, new Criteria());
        $route->load($id, $root, self::request($depth), $context, new Criteria());

        $after($ids, $context, static::getContainer());

        $route->load($id, $root, self::request($depth), $context, new Criteria());
        $response = $route->load($id, $root, self::request($depth), $context, new Criteria());

        static::assertTrue($response->getCategories()->has($id));
        static::assertTrue($response->getCategories()->count() > 0);
    }

    public static function invalidationProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Test root call' => [
            $ids,
            2,
            fn (IdsCollection $ids): string => $ids->get('navigation'),
            function (IdsCollection $ids): void {
            },
            1,
        ];

        yield 'Test when active inside base navigation' => [
            $ids,
            3,
            fn (IdsCollection $ids): string => $ids->get('cat-1.1.1'),
            function (IdsCollection $ids): void {
            },
            1,
        ];

        yield 'Test when active outside base navigation' => [
            $ids,
            1,
            fn (IdsCollection $ids): string => $ids->get('cat-1.1.1'),
            function (IdsCollection $ids): void {
            },
            2,
        ];

        yield 'Test invalidated if category disabled' => [
            $ids,
            1,
            fn (IdsCollection $ids): string => $ids->get('cat-1.1.1'),
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('category.repository')->update([
                    ['id' => $ids->get('cat-1.2.0'), 'active' => false],
                ], Context::createDefaultContext());
            },
            3,
        ];

        yield 'Test invalidated if category deleted' => [
            $ids,
            1,
            fn (IdsCollection $ids): string => $ids->get('cat-1.1.1'),
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('category.repository')->delete([
                    ['id' => $ids->get('cat-1.2.2')],
                ], Context::createDefaultContext());
            },
            3,
        ];

        yield 'Test invalidated if category created' => [
            $ids,
            1,
            fn (IdsCollection $ids): string => $ids->get('cat-1.1.1'),
            function (IdsCollection $ids, SalesChannelContext $context, ContainerInterface $container): void {
                $container->get('category.repository')->create([
                    ['id' => $ids->get('cat-1.2.4'), 'name' => 'cat 1.2.4', 'active' => true],
                ], Context::createDefaultContext());
            },
            3,
        ];
    }

    private static function request(int $depth): Request
    {
        $request = new Request();
        $request->query->set('depth', $depth);
        $request->query->set('buildTree', false);

        return $request;
    }

    private function initData(IdsCollection $ids): void
    {
        $ids->set('navigation', $this->context->getSalesChannel()->getNavigationCategoryId());

        $categories = [
            ['id' => $ids->get('cat-1.0.0'), 'parentId' => $ids->get('navigation'), 'name' => 'cat 1.0.0', 'active' => true, 'children' => [
                ['id' => $ids->get('cat-1.1.0'), 'name' => 'cat 1.1.0', 'active' => true, 'children' => [
                    ['id' => $ids->get('cat-1.1.1'), 'name' => 'cat 1.1.1', 'active' => true],
                    ['id' => $ids->get('cat-1.1.2'), 'name' => 'cat 1.1.2', 'active' => true],
                    ['id' => $ids->get('cat-1.1.3'), 'name' => 'cat 1.1.3', 'active' => true],
                ]],
                ['id' => $ids->get('cat-1.2.0'), 'name' => 'cat 1.2.0', 'active' => true, 'children' => [
                    ['id' => $ids->get('cat-1.2.1'), 'name' => 'cat 1.2.1', 'active' => true],
                    ['id' => $ids->get('cat-1.2.2'), 'name' => 'cat 1.2.2', 'active' => true],
                    ['id' => $ids->get('cat-1.2.3'), 'name' => 'cat 1.2.3', 'active' => true],
                ]],
            ]],
            ['id' => $ids->get('cat-2.0.0'), 'parentId' => $ids->get('navigation'), 'name' => 'cat 2.0.0', 'active' => true, 'children' => [
                ['id' => $ids->get('cat-2.1.0'), 'name' => 'cat 2.1.0', 'active' => true],
                ['id' => $ids->get('cat-2.2.0'), 'name' => 'cat 2.2.0', 'active' => true],
            ]],
        ];

        static::getContainer()->get('category.repository')->create($categories, Context::createDefaultContext());
    }
}
