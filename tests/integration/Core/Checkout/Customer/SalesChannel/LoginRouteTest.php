<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartPersister;
use Cicada\Core\Checkout\Cart\SalesChannel\CartService;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\Exception\CustomerNotFoundException;
use Cicada\Core\Checkout\Customer\SalesChannel\LoginRoute;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\ContextTokenResponse;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class LoginRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelApiTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerRepository;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testInvalidCredentials(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => 'foo',
                    'password' => 'foo12345',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('Unauthorized', $response['errors'][0]['title']);
    }

    public function testEmptyRequest(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__CUSTOMER_AUTH_BAD_CREDENTIALS', $response['errors'][0]['code']);
    }

    public function testValidLogin(): void
    {
        $email = Uuid::randomHex() . '@exämple.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => '12345678',
                ]
            );

        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);
    }

    public function testItNotUpdatesCustomerLanguageIdOnValidLogin(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email, null, true, $this->getZhCnLanguageId());

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => '12345678',
                ],
            );

        static::assertEquals(
            $this->getZhCnLanguageId(),
            $this->customerRepository->search(
                new Criteria([$customerId]),
                Context::createDefaultContext()
            )->getEntities()->first()?->getLanguageId()
        );
    }

    public function testValidLoginWithOneInactive(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        // Inactive user with different password
        $this->createCustomer($email, null, false);
        // Active user with correct password
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => '12345678',
                ]
            );

        $response = $this->browser->getResponse();

        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);
    }

    public function testLoginWithInvalidBoundSalesChannelId(): void
    {
        static::expectException(CustomerNotFoundException::class);

        $email = Uuid::randomHex() . '@example.com';
        $salesChannel = $this->createSalesChannel([
            'id' => Uuid::randomHex(),
        ]);

        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex(), [
            'id' => Uuid::randomHex(),
        ], null);

        $this->createCustomer($email, $salesChannel['id']);

        $loginRoute = static::getContainer()->get(LoginRoute::class);

        $requestDataBag = new RequestDataBag(['email' => $email, 'password' => '12345678']);

        $success = $loginRoute->login($requestDataBag, $salesChannelContext);
        static::assertInstanceOf(ContextTokenResponse::class, $success);

        $loginRoute->login($requestDataBag, $salesChannelContext);
    }

    public function testLoginSuccessRestoreCustomerContext(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email);
        $contextToken = Uuid::randomHex();

        $salesChannelContext = $this->createSalesChannelContext($contextToken, [], $customerId);
        $this->createCart($contextToken, $salesChannelContext);

        $loginRoute = static::getContainer()->get(LoginRoute::class);

        $request = new RequestDataBag(['email' => $email, 'password' => '12345678']);

        $response = $loginRoute->login($request, $salesChannelContext);

        // Token is replace as there're no customer token in the database
        static::assertNotEquals($contextToken, $oldToken = $response->getToken());

        $salesChannelContext = $this->createSalesChannelContext('123456789', [], $customerId);

        $response = $loginRoute->login($request, $salesChannelContext);

        // Previous token is restored
        static::assertEquals($oldToken, $response->getToken());

        // Previous Cart is restored
        $salesChannelContext = $this->createSalesChannelContext($oldToken, [], $customerId);
        $oldCartExists = static::getContainer()->get(CartService::class)->getCart($oldToken, $salesChannelContext);

        static::assertInstanceOf(Cart::class, $oldCartExists);
        static::assertEquals($oldToken, $oldCartExists->getToken());
    }

    public function testCustomerHaveDifferentCartsOnEachSalesChannel(): void
    {
        $email = Uuid::randomHex() . '@example.com';
        $customerId = $this->createCustomer($email);

        $this->createSalesChannel([
            'id' => $this->ids->get('sales-channel-1'),
            'domains' => [
                [
                    'url' => 'http://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);

        $this->createSalesChannel([
            'id' => $this->ids->get('sales-channel-2'),
            'domains' => [
                [
                    'url' => 'http://test.en',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);

        $salesChannelContext1 = $this->createSalesChannelContext($this->ids->get('context-1'), [], $customerId, $this->ids->get('sales-channel-1'));

        $salesChannelContext2 = $this->createSalesChannelContext($this->ids->get('context-2'), [], $customerId, $this->ids->get('sales-channel-2'));

        $this->createCart($this->ids->get('context-1'), $salesChannelContext1);

        $this->createCart($this->ids->get('context-2'), $salesChannelContext2);

        $loginRoute = static::getContainer()->get(LoginRoute::class);

        $request = new RequestDataBag(['email' => $email, 'password' => '12345678']);

        $responseSalesChannel1 = $loginRoute->login($request, $salesChannelContext1);

        $responseSalesChannel2 = $loginRoute->login($request, $salesChannelContext2);

        static::assertNotEquals($responseSalesChannel1->getToken(), $responseSalesChannel2->getToken());

        $cartService = static::getContainer()->get(CartService::class);

        $cartFromSalesChannel1 = $cartService->getCart($responseSalesChannel1->getToken(), $salesChannelContext1, false);
        $cartFromSalesChannel2 = $cartService->getCart($responseSalesChannel2->getToken(), $salesChannelContext2, false);

        static::assertNotEquals($cartFromSalesChannel1->getToken(), $cartFromSalesChannel2->getToken());
    }

    private function createCart(string $contextToken, SalesChannelContext $context): void
    {
        $persister = static::getContainer()->get(CartPersister::class);

        $persister->save(new Cart($contextToken), $context);
    }

    /**
     * @param array<string, mixed> $salesChannelData
     */
    private function createSalesChannelContext(string $contextToken, array $salesChannelData, ?string $customerId, ?string $salesChannelId = null): SalesChannelContext
    {
        if ($customerId) {
            $salesChannelData[SalesChannelContextService::CUSTOMER_ID] = $customerId;
        }

        return static::getContainer()->get(SalesChannelContextFactory::class)->create(
            $contextToken,
            $salesChannelId ?? TestDefaults::SALES_CHANNEL,
            $salesChannelData
        );
    }

    private function createCustomer(?string $email = null, ?string $boundSalesChannelId = null, bool $active = true, ?string $languageId = null): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'country' => ['name' => 'Germany'],
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => TestDefaults::HASHED_PASSWORD,
            'name' => 'Max',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
            'boundSalesChannelId' => $boundSalesChannelId,
            'active' => $active,
        ];

        if ($languageId !== null) {
            $customer['languageId'] = $languageId;
        }

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->create([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
