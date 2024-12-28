<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\SalesChannelApiTestBehaviour;
use Cicada\Core\Framework\Util\Hasher;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerChangePasswordSubscriberTest extends TestCase
{
    use AdminFunctionalTestBehaviour;
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
        $this->browser->setServerParameter('HTTP_SW_CONTEXT_TOKEN', $this->ids->create('token'));

        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testClearLegacyWhenUserChangePassword(): void
    {
        $email = Uuid::randomHex() . '@xchanming.com';
        $password = 'ThisIsNewPassword';

        $newPassword = Uuid::randomHex();
        $customerId = $this->createCustomerWithLegacyPassword($email, $password);

        $context = Context::createDefaultContext();

        $this->getBrowser()->request(
            'PATCH',
            '/api/customer/' . $customerId,
            ['password' => $newPassword]
        );

        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $customerId));

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search($criteria, $context)->first();

        static::assertNotNull($customer->getPassword());
        static::assertNull($customer->getLegacyPassword());
        static::assertNull($customer->getLegacyEncoder());

        $this->loginUser($email, $newPassword);
    }

    public function testNotClearLegacyDataWhenUserNotChangedPassword(): void
    {
        $email = Uuid::randomHex() . '@xchanming.com';
        $password = 'ThisIsNewPassword';

        $customerId = $this->createCustomerWithLegacyPassword($email, $password);
        $context = Context::createDefaultContext();

        $this->getBrowser()->request(
            'PATCH',
            '/api/customer/' . $customerId,
            ['name' => 'Test']
        );

        $response = $this->getBrowser()->getResponse();

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode(), (string) $response->getContent());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $customerId));

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search($criteria, $context)->first();

        static::assertNull($customer->getPassword());
        static::assertNotNull($customer->getLegacyPassword());
        static::assertNotNull($customer->getLegacyEncoder());

        $this->loginUser($email, $password);
    }

    private function loginUser(string $email, string $password): void
    {
        $this->browser
            ->request(
                'POST',
                '/store-api/account/login',
                [
                    'email' => $email,
                    'password' => $password,
                ]
            );

        $response = $this->browser->getResponse();

        // After login successfully, the context token will be set in the header
        $contextToken = $response->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN) ?? '';
        static::assertNotEmpty($contextToken);
    }

    private function createCustomerWithLegacyPassword(string $email, string $password): string
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
                'city' => 'Schoöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => null,
            'legacyPassword' => Hasher::hash($password, 'md5'),
            'legacyEncoder' => 'Md5',
            'name' => 'encryption',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        static::getContainer()->get('customer.repository')->create([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
