<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Exception\BadCredentialsException;
use Cicada\Core\Checkout\Customer\Exception\CustomerNotFoundException;
use Cicada\Core\Checkout\Customer\Exception\PasswordPoliciesUpdatedException;
use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Package('checkout')]
class AccountServiceTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    private AccountService $accountService;

    protected function setUp(): void
    {
        $this->accountService = static::getContainer()->get(AccountService::class);
    }

    public function testLoginById(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $customerId = $this->createCustomerOfSalesChannel($salesChannelContext->getSalesChannelId(), 'foo@bar.com');
        $token = $this->accountService->loginById($customerId, $salesChannelContext);

        $customer = $this->getCustomerFromToken($token, $salesChannelContext->getSalesChannelId());

        static::assertSame('foo@bar.com', $customer->getEmail());
        static::assertSame($customerId, $customer->getId());
    }

    public function testLoginByCredentials(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $customerId = $this->createCustomerOfSalesChannel($salesChannelContext->getSalesChannelId(), 'foo@bar.com');
        $token = $this->accountService->loginByCredentials('foo@bar.com', '12345678', $salesChannelContext);

        $customer = $this->getCustomerFromToken($token, $salesChannelContext->getSalesChannelId());

        static::assertSame('foo@bar.com', $customer->getEmail());
        static::assertSame($customerId, $customer->getId());
    }

    public function testGetCustomerByLogin(): void
    {
        $email = 'johndoe@example.com';

        $context = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'https://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);
        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email);

        $customer = $this->accountService->getCustomerByLogin($email, '12345678', $context);
        static::assertEquals($email, $customer->getEmail());
        static::assertEquals($context->getSalesChannelId(), $customer->getSalesChannelId());
    }

    public function testGetCustomerByLoginWithInvalidPassword(): void
    {
        $this->expectException(BadCredentialsException::class);

        $email = 'johndoe@example.com';

        $context = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'https://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);
        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email);

        $customer = $this->accountService->getCustomerByLogin($email, 'invalid-password', $context);
        static::assertEquals($email, $customer->getEmail());
        static::assertEquals($context->getSalesChannelId(), $customer->getSalesChannelId());
    }

    public function testGetCustomerByLoginWhenCustomersHaveSameEmailReturnsTheLatestCreatedCustomer(): void
    {
        $idCustomer1 = Uuid::randomHex();
        $idCustomer2 = Uuid::randomHex();
        $email = 'johndoe@example.com';
        $context = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'https://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);

        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email, true, true, $idCustomer1, '2022-10-21 10:00:00');
        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email, true, true, $idCustomer2, '2022-10-22 10:00:00');

        $customer = $this->accountService->getCustomerByLogin($email, '12345678', $context);
        static::assertEquals($idCustomer2, $customer->getId());
    }

    public function testGetCustomerByLoginWhenCustomersInDifferentSalesChannelsHaveSameEmail(): void
    {
        $email = 'johndoe@example.com';

        $context1 = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'https://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);
        $this->createCustomerOfSalesChannel($context1->getSalesChannelId(), $email);

        $context2 = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'http://test.en',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);

        $this->createCustomerOfSalesChannel($context2->getSalesChannelId(), $email);

        $customer1 = $this->accountService->getCustomerByLogin($email, '12345678', $context1);

        static::assertEquals($context1->getSalesChannelId(), $customer1->getSalesChannelId());

        $customer2 = $this->accountService->getCustomerByLogin($email, '12345678', $context2);
        static::assertEquals($context2->getSalesChannelId(), $customer2->getSalesChannelId());
    }

    public function testCustomerFailsToLoginByMailWithInactiveAccount(): void
    {
        $email = 'johndoe@example.com';

        $context = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'https://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);
        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email, true, false);

        $this->expectException(CustomerNotFoundException::class);
        $this->expectExceptionMessage('No matching customer for the email "johndoe@example.com" was found.');
        $this->accountService->getCustomerByLogin($email, '12345678', $context);
    }

    public function testGetCustomerByLoginLegacyPasswordIsUpdatedToNewOne(): void
    {
        $idCustomer = Uuid::randomHex();
        $email = 'johndoe@example.com';

        $context = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'http://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);
        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email, true, true, $idCustomer, '2022-10-21 10:00:00', Hasher::hash('12345678', 'md5'), 'Md5');

        $customer = $this->accountService->getCustomerByLogin($email, '12345678', $context);
        static::assertEquals($email, $customer->getEmail());
        static::assertEquals($context->getSalesChannelId(), $customer->getSalesChannelId());

        $customer = $this
            ->getContainer()
            ->get('customer.repository')
            ->search(new Criteria([$idCustomer]), $context->getContext())
            ->first();
        static::assertInstanceOf(CustomerEntity::class, $customer);
        static::assertNull($customer->getLegacyPassword());
        static::assertNull($customer->getLegacyEncoder());
        static::assertNotNull($customer->getPassword());
    }

    public function testCustomerFailsToLoginByLegacyPasswordWithOutdatedPasswordPolicy(): void
    {
        $idCustomer = Uuid::randomHex();
        $email = 'johndoe@example.com';

        $context = $this->createSalesChannelContext([
            'domains' => [
                [
                    'url' => 'http://test.de',
                    'currencyId' => Defaults::CURRENCY,
                    'languageId' => Defaults::LANGUAGE_SYSTEM,
                    'snippetSetId' => $this->getRandomId('snippet_set'),
                ],
            ],
        ]);
        $this->createCustomerOfSalesChannel($context->getSalesChannelId(), $email, true, true, $idCustomer, '2022-10-21 10:00:00', Hasher::hash('test', 'md5'), 'Md5');

        static::expectException(PasswordPoliciesUpdatedException::class);
        static::expectExceptionMessage('Password policies updated.');
        $this->accountService->getCustomerByLogin($email, 'test', $context);
    }

    private function getCustomerFromToken(string $contextToken, string $salesChannelId): CustomerEntity
    {
        $salesChannelContextService = static::getContainer()->get(SalesChannelContextService::class);
        $context = $salesChannelContextService->get(
            new SalesChannelContextServiceParameters($salesChannelId, $contextToken)
        );

        $customer = $context->getCustomer();
        static::assertNotNull($customer);

        return $customer;
    }

    private function createCustomerOfSalesChannel(
        string $salesChannelId,
        string $email,
        bool $boundToSalesChannel = true,
        bool $active = true,
        ?string $customerId = null,
        ?string $createdAt = null,
        ?string $password = TestDefaults::HASHED_PASSWORD,
        ?string $legacyEncoder = null
    ): string {
        $customerId ??= Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'createdAt' => $createdAt,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'customerNumber' => '1337',
            'email' => $email,
            'password' => $legacyEncoder ? null : $password,
            'legacyEncoder' => $legacyEncoder,
            'legacyPassword' => $legacyEncoder ? $password : null,
            'boundSalesChannelId' => $boundToSalesChannel ? $salesChannelId : null,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => $salesChannelId,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'active' => $active,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                ],
            ],
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        static::getContainer()
            ->get('customer.repository')
            ->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
