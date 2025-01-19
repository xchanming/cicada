<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Customer\Validation;

use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Validation\AddressValidationFactory;
use Cicada\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\Generator;
use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AddressValidationFactory::class)]
class AddressValidationFactoryTest extends TestCase
{
    private AddressValidationFactory $addressValidationFactory;

    private SalesChannelContext $salesChannelContext;

    protected function setUp(): void
    {
        $systemConfigServiceMock = $this->createMock(SystemConfigService::class);

        $this->addressValidationFactory = new AddressValidationFactory($systemConfigServiceMock);

        $customer = new CustomerEntity();

        $country = new CountryEntity();

        $address = new CustomerAddressEntity();
        $address->setId('foo');
        $address->setCountryId('foo');
        $address->setCountry($country);

        $customer->setActiveShippingAddress($address);
        $customer->setActiveBillingAddress($address);

        $this->salesChannelContext = Generator::generateSalesChannelContext(customer: $customer);
    }

    public function testDefinitionRulesCreate(): void
    {
        $definition = $this->addressValidationFactory->create($this->salesChannelContext)->getProperties();

        $this->assertAddressDefinition($definition);
    }

    public function testDefinitionRulesUpdate(): void
    {
        $definition = $this->addressValidationFactory->update($this->salesChannelContext)->getProperties();

        static::assertCount(9, $definition);
        static::assertArrayHasKey('id', $definition);

        static::assertCount(2, $definition['id']);
        static::assertInstanceOf(NotBlank::class, $definition['id'][0]);
        static::assertInstanceOf(EntityExists::class, $definition['id'][1]);

        $this->assertAddressDefinition($definition);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function assertAddressDefinition(array $definition): void
    {
        static::assertArrayHasKey('title', $definition);
        static::assertInstanceOf(Length::class, $definition['title'][0]);
        static::assertArrayHasKey('zipcode', $definition);
        static::assertInstanceOf(Length::class, $definition['zipcode'][0]);
        static::assertCount(2, $definition['name']);
        static::assertInstanceOf(NotBlank::class, $definition['name'][0]);
        static::assertInstanceOf(Length::class, $definition['name'][1]);

        static::assertArrayHasKey('salutationId', $definition);
        static::assertArrayHasKey('countryId', $definition);
        static::assertArrayHasKey('countryStateId', $definition);
        static::assertArrayHasKey('name', $definition);
        static::assertArrayHasKey('street', $definition);
        static::assertArrayHasKey('cityId', $definition);

        static::assertCount(1, $definition['salutationId']);
        static::assertInstanceOf(EntityExists::class, $definition['salutationId'][0]);

        static::assertCount(3, $definition['countryId']);
        static::assertInstanceOf(EntityExists::class, $definition['countryId'][0]);
        static::assertInstanceOf(NotBlank::class, $definition['countryId'][1]);
        static::assertInstanceOf(EntityExists::class, $definition['countryId'][2]);

        static::assertCount(1, $definition['countryStateId']);
        static::assertInstanceOf(EntityExists::class, $definition['countryStateId'][0]);

        static::assertCount(1, $definition['cityId']);
        static::assertInstanceOf(NotBlank::class, $definition['cityId'][0]);

        static::assertCount(1, $definition['street']);
        static::assertInstanceOf(NotBlank::class, $definition['street'][0]);
    }
}
