<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Order\Transformer;

use Cicada\Core\Checkout\Cart\Order\Transformer\CustomerTransformer;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Framework\Uuid\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CustomerTransformer::class)]
class CustomerTransformerTest extends TestCase
{
    public function testCustomerTransformationWithCustomFields(): void
    {
        $customerId = Uuid::randomHex();

        $customer = $this->buildCustomerEntity($customerId);

        $customerData = CustomerTransformer::transform($customer);
        static::assertSame([
            'customerId' => $customerId,
            'email' => 'test@example.org',
            'name' => 'Max',
            'username' => 'Smith',
            'nickname' => 'Smith',
            'salutationId' => null,
            'title' => 'Dr.',
            'vatIds' => null,
            'company' => 'Acme Inc.',
            'customerNumber' => 'ABC123XY',
            'remoteAddress' => 'Test street 123, NY',
            'customFields' => ['customerGroup' => 'premium', 'origin' => 'newsletter', 'active' => true],
        ], $customerData);
    }

    private function buildCustomerEntity(string $id): CustomerEntity
    {
        $customerEntity = new CustomerEntity();
        $customerEntity->setId($id);
        $customerEntity->setEmail('test@example.org');
        $customerEntity->setName('Max');
        $customerEntity->setUsername('Smith');
        $customerEntity->setNickname('Smith');
        $customerEntity->setTitle('Dr.');
        $customerEntity->setCompany('Acme Inc.');
        $customerEntity->setCustomerNumber('ABC123XY');
        $customerEntity->setRemoteAddress('Test street 123, NY');
        $customerEntity->setCustomFields(['customerGroup' => 'premium', 'origin' => 'newsletter', 'active' => true]);

        return $customerEntity;
    }
}
