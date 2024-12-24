<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Api\Context\AdminSalesChannelApiSource;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Routing\Event\SalesChannelContextResolvedEvent;
use Cicada\Core\Framework\Routing\RoutingException;
use Cicada\Core\Framework\Routing\SalesChannelRequestContextResolver;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Util\Random;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
class SalesChannelRequestContextResolverTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private IdsCollection $ids;

    private EntityRepository $currencyRepository;

    private SalesChannelContextServiceInterface $contextService;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();
        $this->currencyRepository = static::getContainer()->get('currency.repository');
        $this->contextService = static::getContainer()->get(SalesChannelContextService::class);
    }

    public function testRequestSalesChannelCurrency(): void
    {
        $this->createTestSalesChannel();
        $resolver = static::getContainer()->get(SalesChannelRequestContextResolver::class);

        $phpunit = $this;
        $currencyId = $this->getCurrencyId('USD');

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $this->ids->get('sales-channel'));
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $currencyId);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['store-api']);

        $dispatcher = static::getContainer()->get('event_dispatcher');

        $eventDidRun = false;
        $listenerContextEventClosure = function (SalesChannelContextResolvedEvent $event) use (&$eventDidRun, $phpunit, $currencyId): void {
            $eventDidRun = true;
            $phpunit->assertSame($currencyId, $event->getSalesChannelContext()->getContext()->getCurrencyId());
            $phpunit->assertInstanceOf(SalesChannelApiSource::class, $event->getSalesChannelContext()->getContext()->getSource());
        };

        $this->addEventListener($dispatcher, SalesChannelContextResolvedEvent::class, $listenerContextEventClosure);

        $resolver->resolve($request);

        $dispatcher->removeListener(SalesChannelContextResolvedEvent::class, $listenerContextEventClosure);

        static::assertTrue($eventDidRun, 'The "' . SalesChannelContextResolvedEvent::class . '" Event did not run');
    }

    #[DataProvider('domainData')]
    public function testContextCurrency(string $url, string $currencyCode, string $expectedCode): void
    {
        $this->createTestSalesChannel();
        $currencyId = $this->getCurrencyId($currencyCode);
        $expectedCurrencyId = $expectedCode !== $currencyCode ? $this->getCurrencyId($expectedCode) : $currencyId;

        $context = $this->contextService->get(
            new SalesChannelContextServiceParameters($this->ids->get('sales-channel'), $this->ids->get('token'), Defaults::LANGUAGE_SYSTEM, $currencyId)
        );

        static::assertSame($expectedCurrencyId, $context->getContext()->getCurrencyId());
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function domainData(): array
    {
        return [
            [
                'http://test.store/en-eur',
                'EUR',
                'EUR',
            ],
            [
                'http://test.store/en-usd',
                'USD',
                'USD',
            ],
        ];
    }

    /**
     * @param array<string, bool> $attributes
     */
    #[DataProvider('loginRequiredAnnotationData')]
    public function testLoginRequiredAnnotation(bool $doLogin, bool $isGuest, array $attributes, bool $pass): void
    {
        $resolver = static::getContainer()->get(SalesChannelRequestContextResolver::class);

        $currencyId = $this->getCurrencyId('USD');

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, TestDefaults::SALES_CHANNEL);
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $currencyId);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['store-api']);

        if ($doLogin) {
            $request->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $this->loginCustomer($isGuest));
        }

        foreach ($attributes as $k => $v) {
            $request->attributes->set($k, $v);
        }

        $exception = null;

        try {
            $resolver->resolve($request);
        } catch (\Exception $e) {
            $exception = $e;
        }

        if ($pass) {
            static::assertNull($exception, 'Exception: ' . ($exception !== null ? \print_r($exception->getMessage(), true) : 'No Exception'));
        } else {
            if (Feature::isActive('v6.7.0.0')) {
                static::assertInstanceOf(RoutingException::class, $exception, 'Exception: ' . ($exception !== null ? \print_r($exception->getMessage(), true) : 'No Exception'));
            } else {
                static::assertInstanceOf(CustomerNotLoggedInException::class, $exception, 'Exception: ' . ($exception !== null ? \print_r($exception->getMessage(), true) : 'No Exception'));
            }
        }
    }

    public function testRequestAdminSalesChannelApiSource(): void
    {
        $this->createTestSalesChannel();
        $resolver = static::getContainer()->get(SalesChannelRequestContextResolver::class);

        $phpunit = $this;
        $currencyId = $this->getCurrencyId('USD');

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, $this->ids->get('sales-channel'));
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $currencyId);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['store-api']);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT, Context::createDefaultContext());

        $dispatcher = static::getContainer()->get('event_dispatcher');

        $eventDidRun = false;
        $listenerContextEventClosure = function (SalesChannelContextResolvedEvent $event) use (&$eventDidRun, $phpunit, $currencyId): void {
            $eventDidRun = true;
            $phpunit->assertSame($currencyId, $event->getSalesChannelContext()->getContext()->getCurrencyId());
            $phpunit->assertInstanceOf(AdminSalesChannelApiSource::class, $event->getSalesChannelContext()->getContext()->getSource());
        };

        $this->addEventListener($dispatcher, SalesChannelContextResolvedEvent::class, $listenerContextEventClosure);

        $resolver->resolve($request);

        $dispatcher->removeListener(SalesChannelContextResolvedEvent::class, $listenerContextEventClosure);

        static::assertTrue($eventDidRun, 'The "' . SalesChannelContextResolvedEvent::class . '" Event did not run');
    }

    public function testImitatingUserIdWithCustomer(): void
    {
        $resolver = static::getContainer()->get(SalesChannelRequestContextResolver::class);

        $currencyId = $this->getCurrencyId('USD');

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, TestDefaults::SALES_CHANNEL);
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $currencyId);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['store-api']);
        $request->headers->set(PlatformRequest::HEADER_CONTEXT_TOKEN, $this->loginCustomer(false));

        $request->setSession(new Session(new MockArraySessionStorage()));
        $imitatingUserId = Uuid::randomHex();
        $request->getSession()->set(PlatformRequest::ATTRIBUTE_IMITATING_USER_ID, $imitatingUserId);

        $resolver->resolve($request);

        static::assertEquals($imitatingUserId, $request->getSession()->get(PlatformRequest::ATTRIBUTE_IMITATING_USER_ID));

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        static::assertInstanceOf(SalesChannelContext::class, $context);
        static::assertEquals($imitatingUserId, $context->getImitatingUserId());
    }

    public function testImitatingUserIdClearWithoutCustomer(): void
    {
        $resolver = static::getContainer()->get(SalesChannelRequestContextResolver::class);

        $currencyId = $this->getCurrencyId('USD');

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID, TestDefaults::SALES_CHANNEL);
        $request->attributes->set(SalesChannelRequest::ATTRIBUTE_DOMAIN_CURRENCY_ID, $currencyId);
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['store-api']);

        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->set(PlatformRequest::ATTRIBUTE_IMITATING_USER_ID, Uuid::randomHex());

        $resolver->resolve($request);

        static::assertNull($request->getSession()->get(PlatformRequest::ATTRIBUTE_IMITATING_USER_ID));

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        static::assertInstanceOf(SalesChannelContext::class, $context);
        static::assertNull($context->getImitatingUserId());
    }

    /**
     * @return list<array{0: bool, 1: bool, 2: array<string, bool>, 3: bool}>
     */
    public static function loginRequiredAnnotationData(): array
    {
        $loginRequiredNotAllowGuest = [PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true];
        $loginRequiredAllowGuest = [PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED => true, PlatformRequest::ATTRIBUTE_LOGIN_REQUIRED_ALLOW_GUEST => true];

        return [
            [
                true, // login
                true, // guest
                $loginRequiredNotAllowGuest, // annotation
                false, // pass
            ],
            [
                true,
                false,
                $loginRequiredNotAllowGuest,
                true,
            ],
            [
                false,
                false,
                $loginRequiredNotAllowGuest,
                false,
            ],
            [
                false,
                true,
                $loginRequiredNotAllowGuest,
                false,
            ],
            [
                true,
                true,
                $loginRequiredAllowGuest,
                true,
            ],
            [
                true,
                false,
                $loginRequiredAllowGuest,
                true,
            ],
            [
                false,
                false,
                $loginRequiredAllowGuest,
                false,
            ],
            [
                false,
                true,
                $loginRequiredAllowGuest,
                false,
            ],

            [
                true,
                false,
                [],
                true,
            ],
            [
                false,
                false,
                [],
                true,
            ],
            [
                true,
                true,
                [],
                true,
            ],
            [
                false,
                true,
                [],
                true,
            ],
        ];
    }

    private function loginCustomer(bool $isGuest): string
    {
        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email, $isGuest);

        $token = Random::getAlphanumericString(32);
        static::getContainer()->get(SalesChannelContextPersister::class)->save($token, ['customerId' => $customerId], TestDefaults::SALES_CHANNEL);

        return $token;
    }

    private function getCurrencyId(string $isoCode): ?string
    {
        return $this->currencyRepository->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('isoCode', $isoCode)),
            Context::createDefaultContext()
        )->firstId();
    }

    private function createTestSalesChannel(): void
    {
        $usdCurrencyId = $this->getCurrencyId('USD');

        $this->createSalesChannel([
            'id' => $this->ids->create('sales-channel'),
            'domains' => [
                [
                    'id' => $this->ids->get('eur-domain'),
                    'url' => 'http://test.store/en-eur',
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => Defaults::CURRENCY,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                ],
                [
                    'id' => $this->ids->get('usd-domain'),
                    'url' => 'http://test.store/en-usd',
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'currencyId' => $usdCurrencyId,
                    'snippetSetId' => $this->getSnippetSetIdForLocale('en-GB'),
                ],
            ],
        ]);
    }
}
