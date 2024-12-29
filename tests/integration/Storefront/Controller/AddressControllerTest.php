<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Storefront\Controller;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Script\Debugging\ScriptTraces;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\Test\TestDefaults;
use Cicada\Storefront\Controller\AddressController;
use Cicada\Storefront\Event\StorefrontRenderEvent;
use Cicada\Storefront\Framework\Routing\RequestTransformer;
use Cicada\Storefront\Test\Controller\StorefrontControllerTestBehaviour;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class AddressControllerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use StorefrontControllerTestBehaviour;

    private EntityRepository $customerRepository;

    private string $addressId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerRepository = static::getContainer()->get('customer.repository');

        $this->addressId = Uuid::randomHex();
    }

    public function testDeleteAddressOfOtherCustomer(): void
    {
        [$id1, $id2] = $this->createCustomers();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL, [SalesChannelContextService::CUSTOMER_ID => $id1]);

        $customer = $context->getCustomer();
        static::assertInstanceOf(CustomerEntity::class, $customer);
        static::assertSame($id1, $customer->getId());

        $controller = static::getContainer()->get(AddressController::class);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        static::getContainer()->get('request_stack')->push($request);

        $controller->deleteAddress($id2, $context, $customer);

        $criteria = new Criteria([$id2]);

        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('customer_address.repository');
        $address = $repository->search($criteria, $context->getContext())
            ->get($id2);

        static::assertInstanceOf(CustomerAddressEntity::class, $address);

        $controller->deleteAddress($id1, $context, $customer);

        $criteria = new Criteria([$id1]);

        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('customer_address.repository');
        $exists = $repository
            ->search($criteria, $context->getContext())
            ->has($id2);

        static::assertFalse($exists);
    }

    public function testCreateBillingAddressIsNewSelectedAddress(): void
    {
        [$customerId] = $this->createCustomers();

        $context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(
                Uuid::randomHex(),
                TestDefaults::SALES_CHANNEL,
                [
                    SalesChannelContextService::CUSTOMER_ID => $customerId,
                ]
            );

        $controller = static::getContainer()->get(AddressController::class);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'cicada.test');
        $request->setSession($this->getSession());

        static::getContainer()->get('request_stack')->push($request);

        $customer1 = $context->getCustomer();
        static::assertNotNull($customer1);
        $oldBillingAddressId = $customer1->getDefaultBillingAddressId();
        $oldShippingAddressId = $customer1->getDefaultShippingAddressId();

        $dataBag = $this->getDataBag('billing');
        $controller->addressBook($request, $dataBag, $context, $customer1);
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context->getContext())->first();
        static::assertInstanceOf(CustomerEntity::class, $customer);

        static::assertNotSame($oldBillingAddressId, $customer->getDefaultBillingAddressId());
        static::assertSame($oldShippingAddressId, $customer->getDefaultShippingAddressId());
    }

    public function testCreateShippingAddressIsNewSelectedAddress(): void
    {
        [$customerId] = $this->createCustomers();

        $context = static::getContainer()
            ->get(SalesChannelContextFactory::class)
            ->create(
                Uuid::randomHex(),
                TestDefaults::SALES_CHANNEL,
                [
                    SalesChannelContextService::CUSTOMER_ID => $customerId,
                ]
            );

        $controller = static::getContainer()->get(AddressController::class);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'cicada.test');
        $request->setSession($this->getSession());

        static::getContainer()->get('request_stack')->push($request);

        $customer = $context->getCustomer();
        static::assertNotNull($customer);
        $oldBillingAddressId = $customer->getDefaultBillingAddressId();
        $oldShippingAddressId = $customer->getDefaultShippingAddressId();

        $dataBag = $this->getDataBag('shipping');
        $controller->addressBook($request, $dataBag, $context, $customer);
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context->getContext())->first();
        static::assertInstanceOf(CustomerEntity::class, $customer);

        static::assertNotSame($oldShippingAddressId, $customer->getDefaultShippingAddressId());
        static::assertSame($oldBillingAddressId, $customer->getDefaultBillingAddressId());
    }

    public function testChangeVatIds(): void
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $salutationId = $this->getValidSalutationId();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddress' => [
                'id' => $addressId,
                'salutationId' => $salutationId,
                'name' => 'foo',
                'zipcode' => '48599',
                'city' => 'gronau',
                'street' => 'Schillerstr.',
                'countryId' => $this->getValidCountryId(),
            ],
            'company' => 'nfq',
            'defaultShippingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'not12345',
            'name' => 'First name',
            'salutationId' => $salutationId,
            'customerNumber' => 'not',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->create([$customer], Context::createDefaultContext());

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL, [SalesChannelContextService::CUSTOMER_ID => $customerId]);

        static::assertInstanceOf(CustomerEntity::class, $context->getCustomer());
        static::assertSame($customerId, $context->getCustomer()->getId());

        $controller = static::getContainer()->get(AddressController::class);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'cicada.test');
        static::getContainer()->get('request_stack')->push($request);

        $vatIds = ['DE123456789'];
        $requestDataBag = new RequestDataBag(['vatIds' => $vatIds]);
        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();

        $controller->addressBook($request, $requestDataBag, $context, $customer);

        $criteria = new Criteria([$customerId]);

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search($criteria, $context->getContext())
            ->get($customerId);

        static::assertInstanceOf(CustomerEntity::class, $customer);
        static::assertSame($vatIds, $customer->getVatIds());
    }

    public function testHandleViolationExceptionWhenChangeAddress(): void
    {
        $this->setPostalCodeOfTheCountryToBeRequired();

        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $salutationId = $this->getValidSalutationId();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddress' => [
                'id' => $addressId,
                'salutationId' => $salutationId,
                'name' => 'foo',
                'zipcode' => '48599',
                'city' => 'gronau',
                'street' => 'Schillerstr.',
                'countryId' => $this->getValidCountryId(),
            ],
            'company' => 'ABC',
            'defaultShippingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'not12345',
            'name' => 'Name',
            'salutationId' => $salutationId,
            'customerNumber' => 'not',
        ];

        $this->customerRepository->create([$customer], Context::createDefaultContext());

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL, [SalesChannelContextService::CUSTOMER_ID => $customerId]);

        $controller = static::getContainer()->get(AddressController::class);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'cicada.test');
        static::getContainer()->get('request_stack')->push($request);

        $requestDataBag = new RequestDataBag([
            'changeableAddresses' => new RequestDataBag([
                'changeBilling' => '1',
                'changeShipping' => '',
            ]),
            'addressId' => '',
            'accountType' => '',
            'address' => new RequestDataBag([
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'not',
                'company' => 'not',
                'department' => 'not',
                'street' => 'not',
                'zipcode' => '',
                'city' => 'not',
                'countryId' => $this->getValidCountryId(),
            ]),
        ]);

        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            StorefrontRenderEvent::class,
            function (StorefrontRenderEvent $event): void {
                $data = $event->getParameters();

                static::assertArrayHasKey('formViolations', $data);
                static::assertArrayHasKey('postedData', $data);
            },
            0,
            true
        );

        $controller->addressBook($request, $requestDataBag, $context, $customer);
    }

    public function testHandleExceptionWhenChangeAddress(): void
    {
        $customer = $this->createCustomer();

        $context = static::getContainer()->get(SalesChannelContextFactory::class)
            ->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL, [SalesChannelContextService::CUSTOMER_ID => $customer->getId()]);

        $controller = static::getContainer()->get(AddressController::class);

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);
        $request->attributes->set(RequestTransformer::STOREFRONT_URL, 'cicada.test');
        static::getContainer()->get('request_stack')->push($request);

        $requestDataBag = new RequestDataBag([
            'selectAddress' => new RequestDataBag([
                'id' => 'random',
                'type' => 'random-type',
            ]),
        ]);

        /** @var CustomerEntity $customer */
        $customer = $context->getCustomer();

        $this->addEventListener(
            static::getContainer()->get('event_dispatcher'),
            StorefrontRenderEvent::class,
            function (StorefrontRenderEvent $event): void {
                $data = $event->getParameters();

                static::assertArrayHasKey('success', $data);
                static::assertArrayHasKey('messages', $data);

                static::assertFalse($data['success']);
                static::assertSame('danger', $data['messages']['type']);
            },
            0,
            true
        );

        $controller->addressBook($request, $requestDataBag, $context, $customer);
    }

    public function testAddressListingPageLoadedScriptsAreExecuted(): void
    {
        $browser = $this->login();

        $browser->request('GET', '/account/address');
        $response = $browser->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey('address-listing-page-loaded', $traces);
    }

    public function testAddressDetailPageLoadedScriptsAreExecutedOnAddressCreate(): void
    {
        $browser = $this->login();

        $browser->request('GET', '/account/address/create');
        $response = $browser->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey('address-detail-page-loaded', $traces);
    }

    public function testAddressDetailPageLoadedScriptsAreExecutedOnAddressEdit(): void
    {
        $browser = $this->login();

        $browser->request('GET', '/account/address/' . $this->addressId);
        $response = $browser->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $traces = static::getContainer()->get(ScriptTraces::class)->getTraces();

        static::assertArrayHasKey('address-detail-page-loaded', $traces);
    }

    private function login(): KernelBrowser
    {
        $customer = $this->createCustomer();

        $browser = KernelLifecycleManager::createBrowser($this->getKernel());
        $browser->request(
            'POST',
            $_SERVER['APP_URL'] . '/account/login',
            $this->tokenize('frontend.account.login', [
                'username' => $customer->getEmail(),
                'password' => 'test12345',
            ])
        );
        $response = $browser->getResponse();
        static::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        return $browser;
    }

    private function createCustomer(): CustomerEntity
    {
        $customerId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $this->addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'city' => 'Schöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $this->addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => 'test@example.com',
            'password' => 'test12345',
            'name' => 'Max',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $context = Context::createDefaultContext();

        /** @var EntityRepository<CustomerCollection> $repo */
        $repo = static::getContainer()->get('customer.repository');

        $repo->create([$customer], $context);

        $customer = $repo->search(new Criteria([$customerId]), $context)
            ->getEntities()
            ->first();

        static::assertNotNull($customer);

        return $customer;
    }

    /**
     * @return array<int, string>
     */
    private function createCustomers(): array
    {
        $id1 = Uuid::randomHex();
        $id2 = Uuid::randomHex();

        $salutationId = $this->getValidSalutationId();

        $customers = [
            [
                'id' => $id1,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'defaultShippingAddress' => [
                    'id' => $id1,
                    'name' => 'not',
                    'city' => 'not',
                    'street' => 'not',
                    'zipcode' => 'not',
                    'salutationId' => $salutationId,
                    'country' => ['name' => 'not'],
                ],
                'defaultBillingAddressId' => $id1,
                'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
                'email' => Uuid::randomHex() . '@example.com',
                'password' => 'not12345',
                'name' => 'First name',
                'salutationId' => $salutationId,
                'customerNumber' => 'not',
            ],
            [
                'id' => $id2,
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'defaultShippingAddress' => [
                    'id' => $id2,
                    'name' => 'not',
                    'city' => 'not',
                    'street' => 'not',
                    'zipcode' => 'not',
                    'salutationId' => $salutationId,
                    'country' => ['name' => 'not'],
                ],
                'defaultBillingAddressId' => $id2,
                'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
                'email' => Uuid::randomHex() . '@example.com',
                'password' => 'not12345',
                'name' => 'First name',
                'salutationId' => $salutationId,
                'customerNumber' => 'not',
            ],
        ];

        $this->customerRepository->create($customers, Context::createDefaultContext());

        return [$id1, $id2];
    }

    private function getDataBag(string $type): RequestDataBag
    {
        return new RequestDataBag([
            'changeableAddresses' => new RequestDataBag([
                'changeBilling' => ($type === 'billing') ? '1' : '',
                'changeShipping' => ($type === 'shipping') ? '1' : '',
            ]),
            'addressId' => '',
            'accountType' => '',
            'address' => new RequestDataBag([
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'not',
                'company' => 'not',
                'department' => 'not',
                'street' => 'not',
                'zipcode' => 'not',
                'city' => 'not',
                'countryId' => $this->getValidCountryId(),
            ]),
        ]);
    }

    private function getValidCountryId(?string $salesChannelId = TestDefaults::SALES_CHANNEL): string
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('country.repository');

        $criteria = (new Criteria())->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsFilter('shippingAvailable', true));

        if ($salesChannelId !== null) {
            $criteria->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        }

        return (string) $repository->searchIds($criteria, Context::createDefaultContext())->firstId();
    }

    private function setPostalCodeOfTheCountryToBeRequired(): void
    {
        static::getContainer()->get(Connection::class)
            ->executeStatement('UPDATE `country` SET `postal_code_required` = 1
                 WHERE id = :id', [
                'id' => Uuid::fromHexToBytes($this->getValidCountryId()),
            ]);
    }
}
