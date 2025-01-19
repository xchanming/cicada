<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\SalesChannel;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerRecovery\CustomerRecoveryEntity;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
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
class ResetPasswordRouteTest extends TestCase
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

    public function testWithInvalidHash(): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password-confirm',
                [
                    'hash' => 'lol@lol.de',
                    'newPassword' => 'password123456',
                    'newPasswordConfirm' => 'password123456',
                ]
            );

        $response = json_decode((string) $this->browser->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('errors', $response);
        static::assertSame('CHECKOUT__CUSTOMER_RECOVERY_HASH_EXPIRED', $response['errors'][0]['code']);
    }

    public function testSuccessReset(): void
    {
        $customerId = $this->createCustomer('cicada1234', 'foo-test@test.de');

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password',
                [
                    'email' => 'foo-test@test.de',
                    'storefrontUrl' => 'http://localhost',
                ]
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('customer_recovery.repository');

        /** @var CustomerRecoveryEntity $recovery */
        $recovery = $repo->search($criteria, Context::createDefaultContext())->first();

        static::assertInstanceOf(CustomerRecoveryEntity::class, $recovery);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password-confirm',
                [
                    'hash' => $recovery->getHash(),
                    'newPassword' => 'password123456',
                    'newPasswordConfirm' => 'password123456',
                ]
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode(), (string) $this->browser->getResponse()->getContent());

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => 'foo-test@test.de',
                    'password' => 'password123456',
                ]
            );

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);
    }

    public function testSuccessResetWithLegacyPassword(): void
    {
        $customerId = $this->createCustomer('cicada1234', 'foo-test@test.de', true);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password',
                [
                    'email' => 'foo-test@test.de',
                    'storefrontUrl' => 'http://localhost',
                ]
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));

        /** @var EntityRepository $repo */
        $repo = static::getContainer()->get('customer_recovery.repository');

        /** @var CustomerRecoveryEntity $recovery */
        $recovery = $repo->search($criteria, Context::createDefaultContext())->first();

        static::assertInstanceOf(CustomerRecoveryEntity::class, $recovery);

        $this->browser
            ->request(
                'POST',
                '/store-api/account/recovery-password-confirm',
                [
                    'hash' => $recovery->getHash(),
                    'newPassword' => 'password123456',
                    'newPasswordConfirm' => 'password123456',
                ]
            );

        static::assertSame(200, $this->browser->getResponse()->getStatusCode(), (string) $this->browser->getResponse()->getContent());

        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => 'foo-test@test.de',
                    'password' => 'password123456',
                ]
            );

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);

        $criteria = new Criteria([$customerId]);

        /** @var CustomerEntity $customer */
        $customer = static::getContainer()->get('customer.repository')->search($criteria, Context::createDefaultContext())->first();

        static::assertNull($customer->getLegacyEncoder());
        static::assertNull($customer->getLegacyPassword());
    }

    private function createCustomer(string $password, ?string $email = null, bool $addLegacyPassword = false): string
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
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => $password,
            'name' => 'Max',
            'customerNumber' => '12345',
        ];

        if ($addLegacyPassword) {
            $customer['legacyPassword'] = Hasher::hash('test', 'md5');
            $customer['legacyEncoder'] = 'Md5';
        }

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->create([
            $customer,
        ], Context::createDefaultContext());

        return $customerId;
    }
}
