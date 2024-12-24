<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\Account\Overview;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\SalesChannel\CustomerRoute;
use Cicada\Core\Checkout\Order\OrderCollection;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\SalesChannel\OrderRoute;
use Cicada\Core\Checkout\Order\SalesChannel\OrderRouteResponse;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\EventDispatcher\CollectingEventDispatcher;
use Cicada\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Cicada\Storefront\Page\Account\Overview\AccountOverviewPage;
use Cicada\Storefront\Page\Account\Overview\AccountOverviewPageLoadedEvent;
use Cicada\Storefront\Page\Account\Overview\AccountOverviewPageLoader;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Page\MetaInformation;
use Cicada\Storefront\Page\Page;
use Cicada\Storefront\Pagelet\Newsletter\Account\NewsletterAccountPageletLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(AccountOverviewPageLoader::class)]
class AccountOverviewPageLoaderTest extends TestCase
{
    private CollectingEventDispatcher $eventDispatcher;

    /**
     * @var OrderRoute&MockObject
     */
    private OrderRoute $orderRoute;

    private AccountOverviewPageLoader $pageLoader;

    private AbstractTranslator&MockObject $translator;

    private GenericPageLoader&MockObject $genericPageLoader;

    protected function setUp(): void
    {
        $this->eventDispatcher = new CollectingEventDispatcher();
        $this->orderRoute = $this->createMock(OrderRoute::class);
        $this->translator = $this->createMock(AbstractTranslator::class);
        $this->genericPageLoader = $this->createMock(GenericPageLoader::class);

        $this->pageLoader = new AccountOverviewPageLoader(
            $this->genericPageLoader,
            $this->eventDispatcher,
            $this->orderRoute,
            $this->createMock(CustomerRoute::class),
            $this->createMock(NewsletterAccountPageletLoader::class),
            $this->translator
        );
    }

    public function testLoad(): void
    {
        $order = (new OrderEntity())->assign(['_uniqueIdentifier' => Uuid::randomHex()]);

        $orders = new OrderCollection([$order]);

        $orderResponse = new OrderRouteResponse(
            new EntitySearchResult(
                OrderDefinition::ENTITY_NAME,
                1,
                $orders,
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $this->orderRoute
            ->expects(static::once())
            ->method('load')
            ->willReturn($orderResponse);

        $page = new Page();
        $page->setMetaInformation(new MetaInformation());
        $page->getMetaInformation()?->setMetaTitle('testshop');

        $this->genericPageLoader
            ->expects(static::once())
            ->method('load')
            ->willReturn($page);

        $this->translator
            ->expects(static::once())
            ->method('trans')
            ->willReturn('translated');

        $customer = new CustomerEntity();
        $page = $this->pageLoader->load(new Request(), $this->createMock(SalesChannelContext::class), $customer);

        static::assertEquals($order, $page->getNewestOrder());
        static::assertEquals('translated | testshop', $page->getMetaInformation()?->getMetaTitle());
        static::assertEquals('noindex,follow', $page->getMetaInformation()?->getRobots());

        $events = $this->eventDispatcher->getEvents();
        static::assertCount(2, $events);

        static::assertInstanceOf(OrderRouteRequestEvent::class, $events[0]);
        static::assertInstanceOf(AccountOverviewPageLoadedEvent::class, $events[1]);
    }

    public function testSetStandardMetaDataIfTranslatorIsSet(): void
    {
        $pageLoader = new TestAccountOverviewPageLoader(
            $this->createMock(GenericPageLoader::class),
            $this->eventDispatcher,
            $this->orderRoute,
            $this->createMock(CustomerRoute::class),
            $this->createMock(NewsletterAccountPageletLoader::class),
            $this->translator
        );

        $page = new AccountOverviewPage();

        static::assertNull($page->getMetaInformation());

        $pageLoader->setMetaInformationAccess($page);

        static::assertInstanceOf(MetaInformation::class, $page->getMetaInformation());
    }

    public function testNotSetStandardMetaDataIfTranslatorIsNotSet(): void
    {
        $pageLoader = new TestAccountOverviewPageLoader(
            $this->createMock(GenericPageLoader::class),
            $this->eventDispatcher,
            $this->orderRoute,
            $this->createMock(CustomerRoute::class),
            $this->createMock(NewsletterAccountPageletLoader::class),
            null
        );

        $page = new AccountOverviewPage();

        static::assertNull($page->getMetaInformation());

        $pageLoader->setMetaInformationAccess($page);

        static::assertNull($page->getMetaInformation());
    }
}

/**
 * @internal
 */
class TestAccountOverviewPageLoader extends AccountOverviewPageLoader
{
    public function setMetaInformationAccess(AccountOverviewPage $page): void
    {
        self::setMetaInformation($page);
    }
}
