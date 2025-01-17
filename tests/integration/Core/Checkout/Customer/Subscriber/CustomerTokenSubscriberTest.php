<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\RequestStackTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\PlatformRequest;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\TestDefaults;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerTokenSubscriberTest extends TestCase
{
    use BasicTestDataBehaviour;
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;
    use RequestStackTestBehaviour;

    private Connection $connection;

    private EntityRepository $customerRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = static::getContainer()->get(Connection::class);
        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testCustomerTokenSubscriber(): void
    {
        $customerId = $this->createCustomer();

        $this->connection->insert('sales_channel_api_context', [
            'customer_id' => Uuid::fromHexToBytes($customerId),
            'token' => 'test',
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
            'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'payload' => '{"customerId": "1234"}',
        ]);

        $this->customerRepository->update([
            [
                'id' => $customerId,
                'password' => 'fooo12345',
            ],
        ], Context::createDefaultContext());

        static::assertSame(
            [
                'customerId' => null,
                'billingAddressId' => null,
                'shippingAddressId' => null,
            ],
            \json_decode((string) $this->connection->fetchOne('SELECT payload FROM sales_channel_api_context WHERE token = "test"'), true, 512, \JSON_THROW_ON_ERROR)
        );
    }

    public function testCustomerTokenSubscriberStorefrontShouldStillBeLoggedIn(): void
    {
        $customerId = $this->createCustomer();

        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getToken')->willReturn('test');
        $context->method('getCustomer')->willReturn((new CustomerEntity())->assign(['id' => $customerId]));
        $request->attributes->set(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT, $context);

        static::getContainer()->get('request_stack')->push($request);

        $newToken = null;

        $context->method('assign')->withAnyParameters()->willReturnCallback(function ($array) use ($context, &$newToken) {
            $newToken = $array['token'];

            return $context;
        });

        $this->connection->insert('sales_channel_api_context', [
            'customer_id' => Uuid::fromHexToBytes($customerId),
            'token' => 'test',
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
            'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'payload' => '{"customerId": "1234"}',
        ]);

        $this->customerRepository->update([
            [
                'id' => $customerId,
                'password' => 'fooo12345',
            ],
        ], Context::createDefaultContext());

        static::assertNotNull($newToken);

        static::assertSame(
            [
                'customerId' => '1234',
            ],
            \json_decode((string) $this->connection->fetchOne('SELECT payload FROM sales_channel_api_context WHERE token = ?', [$newToken]), true, 512, \JSON_THROW_ON_ERROR)
        );
    }

    public function testDeleteCustomer(): void
    {
        $customerId = $this->createCustomer();

        $this->connection->insert('sales_channel_api_context', [
            'customer_id' => Uuid::fromHexToBytes($customerId),
            'token' => 'test',
            'sales_channel_id' => Uuid::fromHexToBytes(TestDefaults::SALES_CHANNEL),
            'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'payload' => '{"customerId": "1234"}',
        ]);

        $this->customerRepository->delete([
            [
                'id' => $customerId,
            ],
        ], Context::createDefaultContext());

        static::assertCount(0, $this->connection->fetchAllAssociative('SELECT * FROM sales_channel_api_context WHERE token = ?', ['test']));
    }

    private function createCustomer(): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
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

        $this->customerRepository->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
