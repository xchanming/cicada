<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\Test\Integration\PaymentHandler\TestPaymentHandler;
use Cicada\Core\Test\Integration\Traits\CustomerTestTrait;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[Group('store-api')]
class ChangeProfileRouteTest extends TestCase
{
    use CustomerTestTrait;
    use IntegrationTestBehaviour;

    private KernelBrowser $browser;

    private IdsCollection $ids;

    /**
     * @var EntityRepository<CustomerCollection>
     */
    private EntityRepository $customerRepository;

    private string $customerId;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->createData();

        $this->browser = $this->createCustomSalesChannelBrowser([
            'id' => $this->ids->create('sales-channel'),
        ]);
        $this->assignSalesChannelContext($this->browser);
        $this->customerRepository = static::getContainer()->get('customer.repository');

        $email = Uuid::randomHex() . '@example.com';
        $this->customerId = $this->createCustomer('12345678', $email);

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

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $contextToken);
    }

    public function testEmptyRequest(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);

        $sources = array_column(array_column($response['errors'], 'source'), 'pointer');
        static::assertContains('/name', $sources);
    }

    public function testChangeName(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $this->browser->request('GET', '/store-api/account/customer');
        $customer = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertSame('Max', $customer['name']);
        static::assertSame($this->getValidSalutationId(), $customer['salutationId']);
    }

    public function testChangeProfileDataWithCommercialAccount(): void
    {
        $changeData = [
            'salutationId' => $this->getValidSalutationId(),
            'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
            'name' => 'Max',
            'company' => 'Test Company',
            'vatIds' => [
                'DE123456789',
            ],
        ];
        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                $changeData
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $customer = $this->getCustomer();

        static::assertEquals(['DE123456789'], $customer->getVatIds());
        static::assertEquals($changeData['company'], $customer->getCompany());
        static::assertEquals($changeData['name'], $customer->getName());
    }

    public function testChangeProfileDataWithCommercialAccountAndVatIdsIsEmpty(): void
    {
        $this->setVatIdOfTheCountryToValidateFormat();

        $changeData = [
            'salutationId' => $this->getValidSalutationId(),
            'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
            'name' => 'Max',
            'company' => 'Test Company',
            'vatIds' => [],
        ];
        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                $changeData
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $customer = $this->getCustomer();

        static::assertNull($customer->getVatIds());
        static::assertEquals($changeData['company'], $customer->getCompany());
        static::assertEquals($changeData['name'], $customer->getName());
    }

    public function testChangeProfileWithExistingNotSpecifiedSalutation(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        $salutations = $connection->fetchAllKeyValue('SELECT salutation_key, id FROM salutation');
        static::assertArrayHasKey(SalutationDefinition::NOT_SPECIFIED, $salutations);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                [
                    'name' => 'Max',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);
    }

    public function testChangeProfileToNotSpecifiedWithoutExistingSalutation(): void
    {
        $connection = static::getContainer()->get(Connection::class);

        $connection->executeStatement(
            'DELETE FROM salutation WHERE salutation_key = :salutationKey',
            ['salutationKey' => SalutationDefinition::NOT_SPECIFIED]
        );

        $salutations = $connection->fetchAllKeyValue('SELECT salutation_key, id FROM salutation');
        static::assertArrayNotHasKey(SalutationDefinition::NOT_SPECIFIED, $salutations);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                [
                    'name' => 'Max',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('success', $response);
        static::assertTrue($response['success']);
    }

    public static function dataProviderVatIds(): \Generator
    {
        yield 'Error when vatIds is require but no validate format, and has value is empty' => [
            [''],
            [
                'required' => true,
                'validateFormat' => false,
            ],
            false,
            null,
        ];

        yield 'Error when vatIds is require but no validate format, and without vatIds in parameters' => [
            null,
            [
                'required' => true,
                'validateFormat' => false,
            ],
            false,
            null,
        ];

        yield 'Error when vatIds is require but no validate format, and has value is null and empty value' => [
            [null, ''],
            [
                'required' => true,
                'validateFormat' => false,
            ],
            false,
            null,
        ];

        yield 'Success when vatIds is require but no validate format, and has one of the value is not null' => [
            [null, 'some-text'],
            [
                'required' => true,
                'validateFormat' => false,
            ],
            true,
            ['some-text'],
        ];

        yield 'Success when vatIds is require but no validate format, and has value is random string' => [
            ['some-text'],
            [
                'required' => true,
                'validateFormat' => false,
            ],
            true,
            ['some-text'],
        ];

        yield 'Success when vatIds need to validate format but no require and has value is empty' => [
            [],
            [
                'required' => false,
                'validateFormat' => true,
            ],
            true,
            null,
        ];

        yield 'Success when vatIds need to validate format but no require and has value is null' => [
            [null],
            [
                'required' => false,
                'validateFormat' => true,
            ],
            true,
            null,
        ];

        yield 'Success when vatIds need to validate format but no require and has value is blank' => [
            [''],
            [
                'required' => false,
                'validateFormat' => true,
            ],
            true,
            null,
        ];

        yield 'Error when vatIds need to validate format but no require and has value is boolean' => [
            [true],
            [
                'required' => false,
                'validateFormat' => true,
            ],
            false,
            null,
        ];

        yield 'Error when vatIds need to validate format but no require and has value is incorrect format' => [
            ['random-string'],
            [
                'required' => false,
                'validateFormat' => true,
            ],
            false,
            null,
        ];

        yield 'Success when vatIds need to validate format but no require and has value is correct format' => [
            ['123456789'],
            [
                'required' => false,
                'validateFormat' => true,
            ],
            true,
            ['123456789'],
        ];
    }

    /**
     * @param array<string, bool> $constraint
     * @param array<string|null>|null $vatIds
     * @param array<string>|null $expectedVatIds
     */
    #[DataProvider('dataProviderVatIds')]
    public function testChangeVatIdsOfCommercialAccount(?array $vatIds, array $constraint, bool $shouldBeValid, ?array $expectedVatIds): void
    {
        if (isset($constraint['required']) && $constraint['required']) {
            $this->setVatIdOfTheCountryToBeRequired();
        }

        if (isset($constraint['validateFormat']) && $constraint['validateFormat']) {
            $this->setVatIdOfTheCountryToValidateFormat();
        }

        $changeData = [
            'salutationId' => $this->getValidSalutationId(),
            'accountType' => CustomerEntity::ACCOUNT_TYPE_BUSINESS,
            'name' => 'Max',
            'company' => 'Test Company',
        ];
        if ($vatIds !== null) {
            $changeData['vatIds'] = $vatIds;
        }

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                $changeData
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        if (!$shouldBeValid) {
            static::assertArrayHasKey('errors', $response);

            $sources = array_column(array_column($response['errors'], 'source'), 'pointer');
            static::assertContains('/vatIds', $sources);

            return;
        }

        static::assertTrue($response['success']);

        $customer = $this->getCustomer();

        if ($expectedVatIds === null) {
            static::assertNull($customer->getVatIds());
        } else {
            static::assertEquals($expectedVatIds, $customer->getVatIds());
        }

        static::assertEquals($changeData['company'], $customer->getCompany());
        static::assertEquals($changeData['name'], $customer->getName());
    }

    public function testChangeProfileDataWithPrivateAccount(): void
    {
        $changeData = [
            'salutationId' => $this->getValidSalutationId(),
            'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
            'name' => 'FirstName',
        ];
        $this->browser->request(
            'POST',
            '/store-api/account/change-profile',
            $changeData
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $customer = $this->getCustomer();

        static::assertNull($customer->getVatIds());
        static::assertEquals('', $customer->getCompany());
        static::assertEquals($changeData['name'], $customer->getName());
    }

    public function testChangeSuccessWithNewsletterRecipient(): void
    {
        $this->browser
            ->request(
                'GET',
                '/store-api/account/customer',
                [
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        $this->browser
            ->request(
                'POST',
                '/store-api/newsletter/subscribe',
                [
                    'email' => $response['email'],
                    'name' => $response['name'],
                    'option' => 'direct',
                    'storefrontUrl' => 'http://localhost',
                ]
            );

        /** @var array<string, string> $newsletterRecipient */
        $newsletterRecipient = static::getContainer()->get(Connection::class)
            ->fetchAssociative('SELECT * FROM newsletter_recipient WHERE status = "direct" AND email = ?', [$response['email']]);

        static::assertSame($newsletterRecipient['name'], $response['name']);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/change-profile',
                [
                    'salutationId' => $this->getValidSalutationId(),
                    'accountType' => CustomerEntity::ACCOUNT_TYPE_PRIVATE,
                    'name' => 'FirstName',
                ]
            );

        /** @var array<string, string> $newsletterRecipient */
        $newsletterRecipient = static::getContainer()->get(Connection::class)
            ->fetchAssociative('SELECT * FROM newsletter_recipient WHERE status = "direct" AND email = ?', [$response['email']]);

        static::assertEquals($newsletterRecipient['name'], 'Max');
    }

    public function testChangeWithAllowedAccountType(): void
    {
        /** @var string[] $accountTypes */
        $accountTypes = static::getContainer()->getParameter('customer.account_types');
        static::assertIsArray($accountTypes);
        $accountType = $accountTypes[array_rand($accountTypes)];

        $changeData = [
            'accountType' => $accountType,
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'company' => 'Test Company',
            'vatIds' => [
                'DE123456789',
            ],
        ];

        $this->browser->request(
            'POST',
            '/store-api/account/change-profile',
            $changeData
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $customer = $this->getCustomer();

        static::assertSame($accountType, $customer->getAccountType());
    }

    public function testProfileCanBeChangedWithEmptyAccountType(): void
    {
        $customer = $this->getCustomer();
        $currentSalutationId = $customer->getSalutationId();
        $salutationIds = $this->getValidSalutationIds();
        static::assertNotEmpty($salutationIds);

        $updateSalutationId = null;
        foreach ($salutationIds as $salutationId) {
            if ($currentSalutationId === $salutationId) {
                continue;
            }

            $updateSalutationId = $salutationId;

            break;
        }

        static::assertNotNull($updateSalutationId);

        $changeData = [
            'accountType' => '',
            'salutationId' => $updateSalutationId,
            'name' => 'Max',
            'company' => 'Test Company',
            'vatIds' => [
                'DE123456789',
            ],
        ];
        $this->browser->request(
            'POST',
            '/store-api/account/change-profile',
            $changeData
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $customer = $this->getCustomer();
        static::assertEquals($updateSalutationId, $customer->getSalutationId());
    }

    public function testChangeWithWrongAccountType(): void
    {
        /** @var string[] $accountTypes */
        $accountTypes = static::getContainer()->getParameter('customer.account_types');
        static::assertIsArray($accountTypes);
        $notAllowedAccountType = implode('', $accountTypes);
        $changeData = [
            'accountType' => $notAllowedAccountType,
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'company' => 'Test Company',
            'vatIds' => [
                'DE123456789',
            ],
        ];

        $this->browser->request(
            'POST',
            '/store-api/account/change-profile',
            $changeData
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        static::assertEquals(Response::HTTP_BAD_REQUEST, $this->browser->getResponse()->getStatusCode());
        static::assertArrayHasKey('errors', $response);
        static::assertCount(1, $response['errors']);
        static::assertIsArray($response['errors'][0]);
        static::assertEquals('VIOLATION::NO_SUCH_CHOICE_ERROR', $response['errors'][0]['code']);
    }

    public function testChangeWithoutAccountTypeFallbackToDefaultValue(): void
    {
        $changeData = [
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'company' => 'Test Company',
            'vatIds' => [
                'DE123456789',
            ],
        ];

        $this->browser->request(
            'POST',
            '/store-api/account/change-profile',
            $changeData
        );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertTrue($response['success']);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->customerId));

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search($criteria, Context::createDefaultContext())->first();

        $customerDefinition = new CustomerDefinition();
        static::assertArrayHasKey('accountType', $customerDefinition->getDefaults());
        static::assertSame($customerDefinition->getDefaults()['accountType'], $customer->getAccountType());
    }

    /**
     * @return string[]
     */
    private function getValidSalutationIds(): array
    {
        /** @var EntityRepository $repository */
        $repository = static::getContainer()->get('salutation.repository');

        $criteria = (new Criteria())
            ->addSorting(new FieldSorting('salutationKey'));

        /** @var string[] $ids */
        $ids = $repository->searchIds($criteria, Context::createDefaultContext())->getIds();

        return $ids;
    }

    private function createData(): void
    {
        $data = [
            [
                'id' => $this->ids->create('payment'),
                'name' => $this->ids->get('payment'),
                'technicalName' => 'payment_test',
                'active' => true,
                'handlerIdentifier' => TestPaymentHandler::class,
                'availabilityRule' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'asd',
                    'priority' => 2,
                ],
            ],
            [
                'id' => $this->ids->create('payment2'),
                'name' => $this->ids->get('payment2'),
                'technicalName' => 'payment_test2',
                'active' => true,
                'handlerIdentifier' => TestPaymentHandler::class,
                'availabilityRule' => [
                    'id' => Uuid::randomHex(),
                    'name' => 'asd',
                    'priority' => 2,
                ],
            ],
        ];

        static::getContainer()->get('payment_method.repository')
            ->create($data, Context::createDefaultContext());
    }

    private function createCustomer(?string $password = null, ?string $email = null, ?bool $guest = false): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        if ($email === null) {
            $email = Uuid::randomHex() . '@example.com';
        }

        if ($password === null) {
            $password = Uuid::randomHex();
        }

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'name' => 'Max',
                'street' => 'Musterstraße 1',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId($this->ids->create('sales-channel')),
            ],
            'defaultBillingAddressId' => $addressId,

            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => $password,
            'name' => 'Max',
            'guest' => $guest,
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->create([$customer], Context::createDefaultContext());

        return $customerId;
    }

    private function setVatIdOfTheCountryToValidateFormat(): void
    {
        static::getContainer()->get(Connection::class)
            ->executeStatement(
                'UPDATE `country` SET `check_vat_id_pattern` = 1, `vat_id_pattern` = "(DE)?[0-9]{9}"
                 WHERE id = :id',
                [
                    'id' => Uuid::fromHexToBytes($this->getValidCountryId($this->ids->create('sales-channel'))),
                ]
            );
    }

    private function setVatIdOfTheCountryToBeRequired(): void
    {
        static::getContainer()->get(Connection::class)
            ->executeStatement(
                'UPDATE `country` SET `vat_id_required` = 1
                 WHERE id = :id',
                [
                    'id' => Uuid::fromHexToBytes($this->getValidCountryId($this->ids->create('sales-channel'))),
                ]
            );
    }

    private function getCustomer(): CustomerEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $this->customerId));

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search($criteria, Context::createDefaultContext())->first();

        return $customer;
    }
}
