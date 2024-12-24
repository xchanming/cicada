<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Subscriber;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Salutation\SalutationDefinition;
use Cicada\Core\Test\Stub\Framework\IdsCollection;
use Cicada\Core\Test\TestDefaults;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerSalutationSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;

    private IdsCollection $ids;

    private EntityRepository $customerRepository;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->ids = new IdsCollection();

        $this->connection = KernelLifecycleManager::getConnection();

        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testSetDefaultSalutationWithExistingNotSpecifiedSalutation(): void
    {
        $salutations = $this->connection->fetchAllKeyValue('SELECT salutation_key, id FROM salutation');
        static::assertArrayHasKey(SalutationDefinition::NOT_SPECIFIED, $salutations);

        $this->createCustomer();

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search(
            new Criteria([$this->ids->get('customer')]),
            Context::createDefaultContext()
        )->first();

        static::assertNotNull($customer->getSalutationId());
    }

    public function testSetDefaultSalutationToNotSpecifiedWithoutExistingSalutation(): void
    {
        $this->connection->executeStatement(
            '
					DELETE FROM salutation WHERE salutation_key = :salutationKey
				',
            ['salutationKey' => SalutationDefinition::NOT_SPECIFIED]
        );

        $salutations = $this->connection->fetchAllKeyValue('SELECT salutation_key, id FROM salutation');
        static::assertArrayNotHasKey(SalutationDefinition::NOT_SPECIFIED, $salutations);

        $this->createCustomer();

        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search(
            new Criteria([$this->ids->get('customer')]),
            Context::createDefaultContext()
        )->first();

        static::assertNull($customer->getSalutationId());
    }

    private function createCustomer(): void
    {
        $customerId = $this->ids->create('customer');
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultShippingAddress' => [
                'id' => $addressId,
                'firstName' => 'Max',
                'lastName' => 'Mustermann',
                'street' => 'Musterstraße 1',
                'city' => 'Schöppingen',
                'zipcode' => '12345',
                'salutationId' => $this->getValidSalutationId(),
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => Uuid::randomHex() . '@example.com',
            'password' => Uuid::randomHex(),
            'firstName' => 'Max',
            'lastName' => 'Mustermann',
            'guest' => false,
            'salutationId' => null,
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        $this->customerRepository->create([$customer], Context::createDefaultContext());
    }
}
