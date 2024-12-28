<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\SalesChannel\UpsertAddressRoute;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Framework\Validation\DataBag\RequestDataBag;
use Cicada\Core\Framework\Validation\DataValidationFactoryInterface;
use Cicada\Core\Framework\Validation\DataValidator;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\StoreApiCustomFieldMapper;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class UpsertAddressRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    private EntityRepository $addressRepository;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
        $this->addressRepository = static::getContainer()->get('customer_address.repository');

        $email = Uuid::randomHex() . '@example.com';
        $this->createCustomer($email);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                \json_encode([
                    'email' => $email,
                    'password' => '12345678',
                ], \JSON_THROW_ON_ERROR)
            );

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('addressDataProvider')]
    public function testCreateAddress(array $data): void
    {
        $data['countryId'] = $this->getValidCountryId();

        if (\array_key_exists('salutationId', $data)) {
            $data['salutationId'] = $this->getValidSalutationId();
        }

        $this->browser
            ->request(
                'POST',
                '/store-api/account/address',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                \json_encode($data, \JSON_THROW_ON_ERROR)
            );

        $response = $this->browser->getResponse();
        $content = \json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        static::assertArrayHasKey('id', $content);

        foreach ($data as $key => $val) {
            static::assertSame($val, $content[$key]);
        }

        // Check existence
        /** @var CustomerAddressEntity $address */
        $address = $this->addressRepository->search(new Criteria([$content['id']]), Context::createDefaultContext())->first();
        $serializedAddress = $address->jsonSerialize();

        foreach ($data as $key => $val) {
            static::assertSame($val, $serializedAddress[$key]);
        }
    }

    public function testRequestWithNoParameters(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/address'
            );

        $response = \json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertGreaterThanOrEqual(1, is_countable($response['errors']) ? \count($response['errors']) : 0);
    }

    public function testUpdateExistingAddress(): void
    {
        // Fetch address
        $this->browser
            ->request(
                'POST',
                '/store-api/account/customer'
            );

        $response = \json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $addressId = $response['defaultBillingAddressId'];

        $this->browser
            ->request(
                'POST',
                '/store-api/account/list-address'
            );

        $address = \json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['elements'][0];
        $address['name'] = __FUNCTION__;

        // Update
        $this->browser
            ->request(
                'PATCH',
                '/store-api/account/address/' . $addressId,
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                \json_encode($address, \JSON_THROW_ON_ERROR)
            );

        static::assertSame(Response::HTTP_OK, $this->browser->getResponse()->getStatusCode());

        // Verify
        $this->browser
            ->request(
                'POST',
                '/store-api/account/list-address'
            );

        $updatedAddress = \json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR)['elements'][0];
        unset($address['updatedAt'], $updatedAddress['updatedAt']);

        static::assertSame($address, $updatedAddress);
    }

    public function testCreateAddressForGuest(): void
    {
        $customerId = $this->createCustomer(null, true);
        $contextToken = $this->getLoggedInContextToken($customerId, $this->ids->get('sales-channel'));
        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);

        $data = [
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Test',
            'street' => 'Test',
            'city' => 'Test',
            'zipcode' => 'Test',
            'countryId' => $this->getValidCountryId(),
        ];

        $this->browser
            ->request(
                'POST',
                '/store-api/account/address',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                \json_encode($data, \JSON_THROW_ON_ERROR)
            );

        $response = \json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('id', $response);

        foreach ($data as $key => $val) {
            static::assertSame($val, $response[$key]);
        }

        // Check existence
        /** @var CustomerAddressEntity $address */
        $address = $this->addressRepository->search(new Criteria([$response['id']]), Context::createDefaultContext())->first();

        foreach ($data as $key => $val) {
            static::assertSame($val, $address->jsonSerialize()[$key]);
        }
    }

    public function testCustomFields(): void
    {
        $addressRepository = $this->createMock(EntityRepository::class);
        $addressRepository
            ->method('searchIds')
            ->willReturn(new IdSearchResult(1, [['data' => ['address-1'], 'primaryKey' => 'address-1']], new Criteria(), Context::createDefaultContext()));

        $result = $this->createMock(EntitySearchResult::class);
        $result->method('first')
            ->willReturn(new CustomerAddressEntity());

        $addressRepository
            ->method('search')
            ->willReturn($result);

        $addressRepository
            ->method('upsert')
            ->with([
                [
                    'salutationId' => '1',
                    'name' => null,
                    'street' => null,
                    'city' => null,
                    'zipcode' => null,
                    'countryId' => null,
                    'countryStateId' => null,
                    'company' => null,
                    'department' => null,
                    'title' => null,
                    'phoneNumber' => null,
                    'additionalAddressLine1' => null,
                    'additionalAddressLine2' => null,
                    'id' => 'test',
                    'customerId' => 'test',
                    'customFields' => [
                        'mapped' => 1,
                    ],
                ],
            ]);

        $customFieldMapper = new StoreApiCustomFieldMapper($this->createMock(Connection::class), [
            CustomerAddressDefinition::ENTITY_NAME => [
                ['name' => 'mapped', 'type' => 'int'],
            ],
        ]);

        $route = new UpsertAddressRoute(
            $addressRepository,
            $this->createMock(DataValidator::class),
            new EventDispatcher(),
            $this->createMock(DataValidationFactoryInterface::class),
            $this->createMock(SystemConfigService::class),
            $customFieldMapper,
            $this->createMock(EntityRepository::class),
        );

        $customer = new CustomerEntity();
        $customer->setId('test');
        $route->upsert('test', new RequestDataBag([
            'customFields' => [
                'bla' => 'bla',
                'mapped' => '1',
            ],
            'salutationId' => '1',
        ]), $this->createMock(SalesChannelContext::class), $customer);
    }

    public static function addressDataProvider(): \Generator
    {
        yield 'salutation' => [
            [
                'salutationId' => '',
                'name' => 'Test',
                'street' => 'Test',
                'city' => 'Test',
                'zipcode' => 'Test',
            ],
        ];

        yield 'no-salutation' => [
            [
                'name' => 'Test',
                'street' => 'Test',
                'city' => 'Test',
                'zipcode' => 'Test',
            ],
        ];

        yield 'empty-salutation' => [
            [
                'salutationId' => null,
                'name' => 'Test',
                'street' => 'Test',
                'city' => 'Test',
                'zipcode' => 'Test',
            ],
        ];
    }
}
