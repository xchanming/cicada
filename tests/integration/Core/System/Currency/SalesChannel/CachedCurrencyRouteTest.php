<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\System\Currency\SalesChannel;

use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseHelper\CallableClass;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Currency\Event\CurrencyRouteCacheTagsEvent;
use Cicada\Core\System\Currency\SalesChannel\CachedCurrencyRoute;
use Cicada\Core\System\Currency\SalesChannel\CurrencyRoute;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated tag:v6.7.0 - Remove full class
 *
 * @internal
 */
#[Group('cache')]
#[Group('store-api')]
class CachedCurrencyRouteTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private const ALL_TAG = 'test-tag';

    private const CURRENCY = [
        'name' => 'test',
        'factor' => 1,
        'isoCode' => 'aa',
        'itemRounding' => ['decimals' => 2, 'interval' => 0.01, 'roundForNet' => true],
        'totalRounding' => ['decimals' => 2, 'interval' => 0.01, 'roundForNet' => true],
        'shortName' => 'test',
        'symbol' => '€',
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
            ->addListener(CurrencyRouteCacheTagsEvent::class, static function (CurrencyRouteCacheTagsEvent $event): void {
                $event->addTags([self::ALL_TAG]);
            });

        $route = static::getContainer()->get(CurrencyRoute::class);
        static::assertInstanceOf(CachedCurrencyRoute::class, $route);

        $listener = $this->getMockBuilder(CallableClass::class)->getMock();
        $listener->expects(static::exactly($calls))->method('__invoke');

        static::getContainer()
            ->get('event_dispatcher')
            ->addListener(CurrencyRouteCacheTagsEvent::class, $listener);

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

        yield 'Cache gets invalidated, if created currency assigned to the sales channel' => [
            function (ContainerInterface $container): void {
            },
            function (ContainerInterface $container) use ($ids): void {
                $currency = array_merge(self::CURRENCY, self::ASSIGNED, ['id' => $ids->get('currency')]);
                $container->get('currency.repository')->create([$currency], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache gets invalidated, if updated currency assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $currency = array_merge(self::CURRENCY, self::ASSIGNED, ['id' => $ids->get('currency')]);
                $container->get('currency.repository')->create([$currency], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $update = ['id' => $ids->get('currency'), 'name' => 'update'];
                $container->get('currency.repository')->update([$update], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache gets invalidated, if deleted currency assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $currency = array_merge(self::CURRENCY, self::ASSIGNED, ['id' => $ids->get('currency')]);
                $container->get('currency.repository')->create([$currency], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $delete = ['id' => $ids->get('currency')];
                $container->get('currency.repository')->delete([$delete], Context::createDefaultContext());
            },
            2,
        ];

        yield 'Cache gets not invalidated, if created currency not assigned to the sales channel' => [
            function (): void {
            },
            function (ContainerInterface $container) use ($ids): void {
                $currency = array_merge(self::CURRENCY, ['id' => $ids->get('currency')]);
                $container->get('currency.repository')->create([$currency], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache gets not invalidated, if updated currency not assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $currency = array_merge(self::CURRENCY, ['id' => $ids->get('currency')]);
                $container->get('currency.repository')->create([$currency], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $update = ['id' => $ids->get('currency'), 'name' => 'update'];
                $container->get('currency.repository')->update([$update], Context::createDefaultContext());
            },
            1,
        ];

        yield 'Cache gets invalidated, if deleted currency is not assigned to the sales channel' => [
            function (ContainerInterface $container) use ($ids): void {
                $currency = array_merge(self::CURRENCY, ['id' => $ids->get('currency')]);
                $container->get('currency.repository')->create([$currency], Context::createDefaultContext());
            },
            function (ContainerInterface $container) use ($ids): void {
                $delete = ['id' => $ids->get('currency')];
                $container->get('currency.repository')->delete([$delete], Context::createDefaultContext());
            },
            2,
        ];
    }
}
