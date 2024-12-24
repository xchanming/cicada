<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Order\Validation;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Order\Validation\OrderValidationFactory;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\Test\Generator;
use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderValidationFactory::class)]
class OrderValidationFactoryTest extends TestCase
{
    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $customer = new CustomerEntity();

        $country = new CountryEntity();

        $address = new CustomerAddressEntity();
        $address->setId('foo');
        $address->setCountryId('foo');
        $address->setCountry($country);

        $customer->setActiveShippingAddress($address);
        $customer->setActiveBillingAddress($address);

        $this->salesChannelContext = Generator::createSalesChannelContext(customer: $customer);
    }

    public function testDefinitionRulesCreate(): void
    {
        $orderValidation = new OrderValidationFactory();
        $definition = $orderValidation->create($this->salesChannelContext)->getProperties();

        static::assertCount(1, $definition);
        static::assertArrayHasKey('tos', $definition);

        static::assertCount(1, $definition['tos']);
        static::assertInstanceOf(NotBlank::class, $definition['tos'][0]);
    }

    public function testDefinitionRulesUpdate(): void
    {
        $orderValidation = new OrderValidationFactory();
        $definition = $orderValidation->create($this->salesChannelContext)->getProperties();

        static::assertCount(1, $definition);
        static::assertInstanceOf(NotBlank::class, $definition['tos'][0]);
    }
}
