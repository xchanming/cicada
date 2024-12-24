<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Storefront\Page\Account\Profile;

use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Adapter\Translation\AbstractTranslator;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\Salutation\SalesChannel\SalutationRoute;
use Cicada\Core\System\Salutation\SalesChannel\SalutationRouteResponse;
use Cicada\Core\System\Salutation\SalutationCollection;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\System\Salutation\SalutationEntity;
use Cicada\Core\System\Salutation\SalutationSorter;
use Cicada\Core\Test\Stub\EventDispatcher\CollectingEventDispatcher;
use Cicada\Storefront\Event\RouteRequest\SalutationRouteRequestEvent;
use Cicada\Storefront\Page\Account\Profile\AccountProfilePage;
use Cicada\Storefront\Page\Account\Profile\AccountProfilePageLoadedEvent;
use Cicada\Storefront\Page\Account\Profile\AccountProfilePageLoader;
use Cicada\Storefront\Page\GenericPageLoader;
use Cicada\Storefront\Page\MetaInformation;
use Cicada\Storefront\Page\Page;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(AccountProfilePageLoader::class)]
class AccountProfilePageLoaderTest extends TestCase
{
    private CollectingEventDispatcher $eventDispatcher;

    private AccountProfilePageLoader $pageLoader;

    private AbstractTranslator&MockObject $translator;

    private GenericPageLoader&MockObject $genericPageLoader;

    private SalutationRoute&MockObject $salutationRoute;

    private SalutationSorter&MockObject $salutationSorter;

    protected function setUp(): void
    {
        $this->eventDispatcher = new CollectingEventDispatcher();
        $this->salutationRoute = $this->createMock(SalutationRoute::class);
        $this->salutationSorter = $this->createMock(SalutationSorter::class);
        $this->translator = $this->createMock(AbstractTranslator::class);
        $this->genericPageLoader = $this->createMock(GenericPageLoader::class);

        $this->pageLoader = new AccountProfilePageLoader(
            $this->genericPageLoader,
            $this->eventDispatcher,
            $this->salutationRoute,
            $this->salutationSorter,
            $this->translator
        );
    }

    public function testLoad(): void
    {
        $salutation = new SalutationEntity();
        $salutation->setId(Uuid::randomHex());

        $salutation2Id = Uuid::randomHex();
        $salutation2 = new SalutationEntity();
        $salutation2->setId($salutation2Id);

        $salutations = new SalutationCollection([$salutation, $salutation2]);
        $salutationResponse = new SalutationRouteResponse(
            new EntitySearchResult(
                SalutationDefinition::ENTITY_NAME,
                2,
                $salutations,
                null,
                new Criteria(),
                Context::createDefaultContext()
            )
        );

        $salutationsSorted = new SalutationCollection([$salutation2, $salutation]);

        $this->salutationRoute
            ->expects(static::once())
            ->method('load')
            ->willReturn($salutationResponse);

        $this->salutationSorter
            ->expects(static::once())
            ->method('sort')
            ->willReturn($salutationsSorted);

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

        $salesChannelContext = $this->getContextWithDummyCustomer();
        $page = $this->pageLoader->load(new Request(), $salesChannelContext);

        static::assertSame($salutationsSorted, $page->getSalutations());
        static::assertEquals('translated | testshop', $page->getMetaInformation()?->getMetaTitle());
        static::assertEquals('noindex,follow', $page->getMetaInformation()?->getRobots());

        $events = $this->eventDispatcher->getEvents();
        static::assertCount(2, $events);

        static::assertInstanceOf(AccountProfilePageLoadedEvent::class, $events[1]);
        static::assertInstanceOf(SalutationRouteRequestEvent::class, $events[0]);
    }

    public function testSetStandardMetaDataIfTranslatorIsSet(): void
    {
        $pageLoader = new TestAccountProfilePageLoader(
            $this->genericPageLoader,
            $this->eventDispatcher,
            $this->salutationRoute,
            $this->salutationSorter,
            $this->translator
        );

        $page = new AccountProfilePage();

        static::assertNull($page->getMetaInformation());

        $pageLoader->setMetaInformationAccess($page);

        static::assertInstanceOf(MetaInformation::class, $page->getMetaInformation());
    }

    public function testNotSetStandardMetaDataIfTranslatorIsNotSet(): void
    {
        $pageLoader = new TestAccountProfilePageLoader(
            $this->genericPageLoader,
            $this->eventDispatcher,
            $this->salutationRoute,
            $this->salutationSorter,
            null
        );

        $page = new AccountProfilePage();

        static::assertNull($page->getMetaInformation());

        $pageLoader->setMetaInformationAccess($page);

        static::assertNull($page->getMetaInformation());
    }

    public function testNoCustomerException(): void
    {
        $pageLoader = new AccountProfilePageLoader(
            $this->genericPageLoader,
            $this->eventDispatcher,
            $this->salutationRoute,
            $this->salutationSorter,
            null
        );

        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        static::expectException(CustomerNotLoggedInException::class);

        $this->pageLoader->load(new Request(), $salesChannelContext);
    }

    private function getContextWithDummyCustomer(): SalesChannelContext
    {
        $customer = new CustomerEntity();

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCustomer')
            ->willReturn($customer);

        return $context;
    }
}

/**
 * @internal
 */
class TestAccountProfilePageLoader extends AccountProfilePageLoader
{
    public function setMetaInformationAccess(AccountProfilePage $page): void
    {
        self::setMetaInformation($page);
    }
}
