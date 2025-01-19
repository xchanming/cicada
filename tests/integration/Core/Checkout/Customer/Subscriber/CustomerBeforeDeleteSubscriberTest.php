<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Checkout\Customer\Subscriber;

use Cicada\Core\Checkout\Customer\Event\CustomerDeletedEvent;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @internal
 */
#[Package('checkout')]
class CustomerBeforeDeleteSubscriberTest extends TestCase
{
    use AdminFunctionalTestBehaviour;

    private EntityRepository $customerRepository;

    protected function setUp(): void
    {
        $this->customerRepository = static::getContainer()->get('customer.repository');
    }

    public function testCustomerDeletedEventDispatched(): void
    {
        $email1 = Uuid::randomHex() . '@xchanming.com';
        $email2 = Uuid::randomHex() . '@xchanming.com';

        $customerId1 = $this->createCustomer($email1);
        $customerId2 = $this->createCustomer($email2);

        $context = Context::createDefaultContext();

        $caughtEvents = [];

        $listenerClosure = function (Event $event) use (&$caughtEvents): void {
            $caughtEvents[] = $event;
        };

        static::getContainer()->get('event_dispatcher')->addListener(CustomerDeletedEvent::class, $listenerClosure);

        $this->customerRepository->delete([
            ['id' => $customerId1],
            ['id' => $customerId2],
        ], $context);

        static::assertCount(2, $caughtEvents);

        foreach ($caughtEvents as $event) {
            static::assertInstanceOf(CustomerDeletedEvent::class, $event);
            static::assertContains($event->getCustomer()->getId(), [$customerId1, $customerId2]);
        }
    }

    private function createCustomer(string $email): string
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
                'countryId' => $this->getValidCountryId(),
            ],
            'defaultBillingAddressId' => $addressId,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'email' => $email,
            'password' => TestDefaults::HASHED_PASSWORD,
            'name' => 'encryption',
            'salutationId' => $this->getValidSalutationId(),
            'customerNumber' => '12345',
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        static::getContainer()->get('customer.repository')->create([$customer], Context::createDefaultContext());

        return $customerId;
    }
}
