<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Language\SalesChannel;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Language\Event\LanguageRouteCacheTagsEvent;
use Cicada\Core\System\Language\SalesChannel\CachedLanguageRoute;
use Cicada\Core\System\Language\SalesChannel\LanguageRoute;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[Group('cache')]
#[Group('store-api')]
class CachedLanguageRouteTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private const ALL_TAG = 'test-tag';

    private const LANGUAGE = [
        'name' => 'test',
        'parentId' => Defaults::LANGUAGE_SYSTEM,
        'locale' => ['code' => 'test', 'territory' => 'test', 'name' => 'test'],
    ];

    private const ASSIGNED = [
        'salesChannels' => [['id' => TestDefaults::SALES_CHANNEL]],
    ];

    private SalesChannelContext $context;

    protected function setUp(): void
    {
        Feature::skipTestIfActive('cache_rework', $this);
        parent::setUp();

        $this->context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    #[AfterClass]
    public function cleanup(): void
    {
        static::getContainer()->get('cache.object')
            ->invalidateTags([self::ALL_TAG]);
    }

    #[DataProvider('invalidationProvider')]
    public function testInvalidation(\Closure $before, \Closure $after, int $calls): void
    {
        static::getContainer()->get('cache.object')->invalidateTags([self::ALL_TAG]);

        static::getContainer()->get('event_dispatcher')
            ->addListener(LanguageRouteCacheTagsEvent::class, static function (LanguageRouteCacheTagsEvent $event): void {
                $event->addTags([self::ALL_TAG]);
            });

        $route = static::getContainer()->get(LanguageRoute::class);
        static::assertInstanceOf(CachedLanguageRoute::class, $route);

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::exactly($calls))->method('__invoke');

        static::getContainer()
            ->get('event_dispatcher')
            ->addListener(LanguageRouteCacheTagsEvent::class, $listener);

        $before(static::getContainer());

        $route->load(new Request(), $this->context, new Criteria());
        $route->load(new Request(), $this->context, new Criteria());

        $after(static::getContainer());

        $route->load(new Request(), $this->context, new Criteria());
        $route->load(new Request(), $this->context, new Criteria());
    }

    public static function invalidationProvider(): \Generator
    {
        $ids = new IdsCollection();

        yield 'Cache gets invalidated, if created language assigned to the sales channel' => [
            function (): void {
            },
            function (ContainerInterface $container) use ($ids): void {
                $language = array_merge(self::LANGUAGE, self::ASSIGNED, ['id' => $ids->get('language')]);
                $container->get('language.repository')->create([$language], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache gets invalidated, if updated language assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $language = array_merge(self::LANGUAGE, self::ASSIGNED, ['id' => $ids->get('language')]);
                $container->get('language.repository')->create([$language], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $update = ['id' => $ids->get('language'), 'name' => 'update'];
                $container->get('language.repository')->update([$update], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache gets invalidated, if deleted language assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $language = array_merge(self::LANGUAGE, self::ASSIGNED, ['id' => $ids->get('language')]);
                $container->get('language.repository')->create([$language], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $delete = ['id' => $ids->get('language')];
                $container->get('language.repository')->delete([$delete], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache gets not invalidated, if created language not assigned to the sales channel' => [
            function (): void {
            },
            function (ContainerInterface $container) use ($ids): void {
                $language = array_merge(self::LANGUAGE, ['id' => $ids->get('language')]);
                $container->get('language.repository')->create([$language], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache gets not invalidated, if updated language not assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $language = array_merge(self::LANGUAGE, ['id' => $ids->get('language')]);
                $container->get('language.repository')->create([$language], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $update = ['id' => $ids->get('language'), 'name' => 'update'];
                $container->get('language.repository')->update([$update], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache gets invalidated, if deleted language is not assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $language = array_merge(self::LANGUAGE, ['id' => $ids->get('language')]);
                $container->get('language.repository')->create([$language], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $delete = ['id' => $ids->get('language')];
                $container->get('language.repository')->delete([$delete], Context::createDefaultContext());
            },
            2,
        ];
    }
}
