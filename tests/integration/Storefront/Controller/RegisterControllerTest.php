<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Event\CustomerDoubleOptInRegistrationEvent;
use Cicada\Core\Checkout\Customer\SalesChannel\RegisterConfirmRoute;
use Cicada\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Cicada\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Event\EventData\MailRecipientStruct;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\MailTemplateTestBehaviour;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\QueryDataBag;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\PlatformRequest;
use Cicada\Core\SalesChannelRequest;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Controller\RegisterController;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Cicada\Storefront\Page\Account\CustomerGroupRegistration\CustomerGroupRegistrationPageLoadedHook;
use Cicada\Storefront\Page\Account\CustomerGroupRegistration\CustomerGroupRegistrationPageLoader;
use Cicada\Storefront\Page\Account\Login\AccountLoginPageLoader;
use Cicada\Storefront\Page\Account\Register\AccountRegisterPageLoadedHook;
use Cicada\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedHook;
use Cicada\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoader;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @internal
 */
#[Package('checkout')]
class RegisterControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use MailTemplateTestBehaviour;
    use StorefrontControllerTestBehaviour;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);

        $token = Uuid::randomHex();
        $this->salesChannelContext = $salesChannelContextFactory->create($token, TestDefaults::SALES_CHANNEL);

        $session = $this->getSession();
        static::assertInstanceOf(FlashBagAwareSessionInterface::class, $session);
        $session->getFlashBag()->clear();
    }

    public function testGuestRegisterWithRequirePasswordConfirmation(): void
    {
        $container = static::getContainer();

        $customerRepository = $container->get('customer.repository');

        $config = static::getContainer()->get(SystemConfigService::class);

        $mock = $this->createMock(SystemConfigService::class);

        $mock->expects(static::any())
            ->method('get')
            ->willReturnCallback(function (string $key) use ($config) {
                if ($key === 'core.loginRegistration.requirePasswordConfirmation') {
                    return true;
                }

                return $config->get($key);
            });

        $registerController = new RegisterController(
            $container->get(AccountLoginPageLoader::class),
            $container->get(RegisterRoute::class),
            $container->get(RegisterConfirmRoute::class),
            $container->get(CartService::class),
            $container->get(CheckoutRegisterPageLoader::class),
            $mock,
            $customerRepository,
            $this->createMock(CustomerGroupRegistrationPageLoader::class),
            $container->get('sales_channel_domain.repository')
        );

        $data = $this->getRegistrationData();

        $request = $this->createRequest();

        $response = $registerController->register($request, $data, $this->salesChannelContext);

        $customers = static::getContainer()->get(Connection::class)
            ->fetchAllAssociative('SELECT * FROM customer WHERE email = :mail', ['mail' => $data->get('email')]);

        static::assertEquals(200, $response->getStatusCode());
        static::assertCount(1, $customers);
        static::assertTrue($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));
    }

    public function testGuestRegister(): void
    {
        $data = $this->getRegistrationData();

        $request = $this->createRequest();

        $response = static::getContainer()->get(RegisterController::class)->register($request, $data, $this->salesChannelContext);

        $customers = static::getContainer()->get(Connection::class)
            ->fetchAllAssociative('SELECT * FROM customer WHERE email = :mail', ['mail' => $data->get('email')]);

        static::assertEquals(200, $response->getStatusCode());
        static::assertCount(1, $customers);
        static::assertTrue($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));
    }

    public function testRegisterWithDoubleOptIn(): void
    {
        $container = static::getContainer();

        $customerRepository = $container->get('customer.repository');

        $systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $systemConfigService->set('core.loginRegistration.doubleOptInRegistration', true);

        $event = null;
        $this->catchEvent(CustomerDoubleOptInRegistrationEvent::class, $event);

        $registerController = new RegisterController(
            $container->get(AccountLoginPageLoader::class),
            $container->get(RegisterRoute::class),
            $container->get(RegisterConfirmRoute::class),
            $container->get(CartService::class),
            $container->get(CheckoutRegisterPageLoader::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(CustomerGroupRegistrationPageLoader::class),
            $container->get('sales_channel_domain.repository')
        );

        $registerController->setContainer($container);

        $data = $this->getRegistrationData(false);
        $data->add(['redirectTo' => 'frontend.checkout.confirm.page']);

        $request = $this->createRequest();

        $response = $registerController->register($request, $data, $this->salesChannelContext);

        static::assertFalse($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));

        static::assertEquals(302, $response->getStatusCode());
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals('/account/register', $response->getTargetUrl());

        $session = $this->getSession();
        static::assertInstanceOf(Session::class, $session);
        $success = $session->getFlashBag()->get('success');

        static::assertNotEmpty($success);
        static::assertEquals($container->get('translator')->trans('account.optInRegistrationAlert'), $success[0]);

        static::assertInstanceOf(CustomerDoubleOptInRegistrationEvent::class, $event);
        static::assertMailEvent(CustomerDoubleOptInRegistrationEvent::class, $event, $this->salesChannelContext);
        static::assertMailRecipientStructEvent($this->getMailRecipientStruct($data->all()), $event);

        static::assertStringEndsWith('&redirectTo=frontend.checkout.confirm.page', $event->getConfirmUrl());
    }

    public function testRegisterWithDoubleOptInDomainChanged(): void
    {
        $container = static::getContainer();

        $customerRepository = $container->get('customer.repository');

        $systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $systemConfigService->set('core.loginRegistration.doubleOptInRegistration', true);
        $systemConfigService->set('core.loginRegistration.doubleOptInDomain', 'https://test.test.com');

        $event = null;
        $this->catchEvent(CustomerDoubleOptInRegistrationEvent::class, $event);

        $registerController = new RegisterController(
            $container->get(AccountLoginPageLoader::class),
            $container->get(RegisterRoute::class),
            $container->get(RegisterConfirmRoute::class),
            $container->get(CartService::class),
            $container->get(CheckoutRegisterPageLoader::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(CustomerGroupRegistrationPageLoader::class),
            $container->get('sales_channel_domain.repository')
        );

        $registerController->setContainer($container);

        $data = $this->getRegistrationData(false);
        $data->add(['redirectTo' => 'frontend.checkout.confirm.page']);

        $request = $this->createRequest();

        $response = $registerController->register($request, $data, $this->salesChannelContext);

        static::assertFalse($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));

        static::assertEquals(302, $response->getStatusCode());
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals('/account/register', $response->getTargetUrl());

        $session = $request->getSession();
        static::assertInstanceOf(Session::class, $session);
        $flashBag = $session->getFlashBag();
        $success = $flashBag->get('success');

        static::assertNotEmpty($success);
        static::assertEquals($container->get('translator')->trans('account.optInRegistrationAlert'), $success[0]);

        static::assertInstanceOf(CustomerDoubleOptInRegistrationEvent::class, $event);
        static::assertMailEvent(CustomerDoubleOptInRegistrationEvent::class, $event, $this->salesChannelContext);
        static::assertMailRecipientStructEvent($this->getMailRecipientStruct($data->all()), $event);

        static::assertStringStartsWith('https://test.test.com', $event->getConfirmUrl());
        $systemConfigService->set('core.loginRegistration.doubleOptInRegistration', false);
        $systemConfigService->set('core.loginRegistration.doubleOptInDomain', null);
    }

    public function testConfirmRegisterWithRedirectTo(): void
    {
        $container = static::getContainer();

        /** @var EntityRepository<CustomerCollection> $customerRepository */
        $customerRepository = $container->get('customer.repository');

        $systemConfigService = static::getContainer()->get(SystemConfigService::class);
        $systemConfigService->set('core.loginRegistration.doubleOptInRegistration', true);

        $event = null;
        $this->catchEvent(CustomerDoubleOptInRegistrationEvent::class, $event);

        $registerController = new RegisterController(
            $container->get(AccountLoginPageLoader::class),
            $container->get(RegisterRoute::class),
            $container->get(RegisterConfirmRoute::class),
            $container->get(CartService::class),
            $container->get(CheckoutRegisterPageLoader::class),
            $systemConfigService,
            $customerRepository,
            $this->createMock(CustomerGroupRegistrationPageLoader::class),
            $container->get('sales_channel_domain.repository')
        );

        $registerController->setContainer($container);

        $data = $this->getRegistrationData(false);
        $data->add(['redirectTo' => 'frontend.checkout.confirm.page']);

        $request = $this->createRequest();

        $event = null;
        $this->catchEvent(CustomerDoubleOptInRegistrationEvent::class, $event);

        $registerController->register($request, $data, $this->salesChannelContext);

        static::assertFalse($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));

        static::assertInstanceOf(CustomerDoubleOptInRegistrationEvent::class, $event);

        $customer = $customerRepository->search(new Criteria([$event->getCustomer()->getId()]), $this->salesChannelContext->getContext())->getEntities();
        $queryData = new QueryDataBag();
        $queryData->set('redirectTo', 'frontend.checkout.confirm.page');
        $queryData->set('hash', $customer->first()?->getHash());
        $queryData->set('em', Hasher::hash($event->getCustomer()->getEmail(), 'sha1'));

        $response = $registerController->confirmRegistration($this->salesChannelContext, $queryData);

        static::assertTrue($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT));

        static::assertEquals(302, $response->getStatusCode());
        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertEquals('/checkout/confirm', $response->getTargetUrl());
    }

    public function testAccountRegisterPageLoadedHookScriptsAreExecuted(): void
    {
        $response = $this->request('GET', '/account/register', []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(AccountRegisterPageLoadedHook::HOOK_NAME, $traces);
    }

    public function testCustomerGroupRegistrationPageLoadedHookScriptsAreExecuted(): void
    {
        $ids = new IdsCollection();
        $this->createCustomerGroup($ids);

        $response = $this->request('GET', 'customer-group-registration/' . $ids->get('group'), []);
        static::assertEquals(200, $response->getStatusCode(), print_r($response->getContent(), true));

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(CustomerGroupRegistrationPageLoadedHook::HOOK_NAME, $traces);
    }

    public function testCheckoutRegisterPageLoadedHookScriptsAreExecuted(): void
    {
        $productNumber = ' p1';

        $this->createProduct(Uuid::randomHex(), $productNumber);

        $this->request(
            'POST',
            '/checkout/product/add-by-number',
            $this->tokenize('frontend.checkout.product.add-by-number', [
                'number' => $productNumber,
            ])
        );

        $response = $this->request('GET', '/checkout/register', []);
        static::assertEquals(200, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey(CheckoutRegisterPageLoadedHook::HOOK_NAME, $traces);
    }

    /**
     * @param array<string|int, mixed> $customerData
     */
    private function getMailRecipientStruct(array $customerData): MailRecipientStruct
    {
        return new MailRecipientStruct([
            (string) $customerData['email'] => $customerData['firstName'] . ' ' . $customerData['lastName'],
        ]);
    }

    private function createRequest(): Request
    {
        $request = new Request();
        $request->setSession($this->getSession());
        $request->request->add(['errorRoute' => 'frontend.checkout.register.page']);
        $request->attributes->add(['_route' => 'frontend.checkout.register.page', SalesChannelRequest::ATTRIBUTE_IS_SALES_CHANNEL_REQUEST => true]);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'cicada.test');

        static::getContainer()->get('request_stack')->push($request);

        return $request;
    }

    private function getRegistrationData(?bool $isGuest = true): RequestDataBag
    {
        $data = [
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
            'email' => 'max.mustermann@example.com',
            'emailConfirmation' => 'max.mustermann@example.com',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'storefrontUrl' => 'http://localhost',
            'billingAddress' => [
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'countryId' => $this->getValidCountryId(),
                'street' => 'Musterstrasse 13',
                'zipcode' => '48599',
                'city' => 'Epe',
            ],
        ];

        if (!$isGuest) {
            $data['createCustomerAccount'] = true;
            $data['password'] = TestDefaults::HASHED_PASSWORD;
        }

        return new RequestDataBag($data);
    }

    private function createCustomerGroup(IdsCollection $ids): void
    {
        $salesChannel = static::getContainer()->get('sales_channel.repository')->search(
            (new Criteria())->addFilter(
                new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT),
                new EqualsFilter('domains.url', $_SERVER['APP_URL'])
            ),
            Context::createDefaultContext()
        )->getEntities()->first();

        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);

        static::getContainer()->get('customer_group.repository')->create([
            [
                'id' => $ids->create('group'),
                'registrationActive' => true,
                'name' => 'test',
                'registrationSalesChannels' => [
                    [
                        'id' => $salesChannel->getId(),
                    ],
                ],
            ],
        ], Context::createDefaultContext());
    }

    private function createProduct(string $productId, string $productNumber): void
    {
        $taxId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $salesChannel = static::getContainer()->get('sales_channel.repository')->search(
            (new Criteria())->addFilter(
                new EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_STOREFRONT),
                new EqualsFilter('domains.url', $_SERVER['APP_URL'])
            ),
            Context::createDefaultContext()
        )->getEntities()->first();

        static::assertInstanceOf(SalesChannelEntity::class, $salesChannel);

        $product = [
            'id' => $productId,
            'name' => 'Test product',
            'productNumber' => $productNumber,
            'stock' => 1,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => 15.99, 'net' => 10, 'linked' => false],
            ],
            'tax' => ['id' => $taxId, 'name' => 'testTaxRate', 'taxRate' => 15],
            'categories' => [
                ['id' => $productId, 'name' => 'Test category'],
            ],
            'visibilities' => [
                [
                    'id' => $productId,
                    'salesChannelId' => $salesChannel->getId(),
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];
        static::getContainer()->get('product.repository')->create([$product], $context);
    }
}
