<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\System\SalesChannel\Context;

use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Tax\TaxDetector;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressDefinition;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Cicada\Core\Checkout\Customer\CustomerCollection;
use Cicada\Core\Checkout\Customer\CustomerDefinition;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Payment\PaymentMethodCollection;
use Cicada\Core\Checkout\Payment\PaymentMethodDefinition;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Framework\Api\Context\SalesChannelApiSource;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\Currency\CurrencyEntity;
use Cicada\Core\System\SalesChannel\BaseContext;
use Cicada\Core\System\SalesChannel\Context\AbstractBaseContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\Tax\TaxCollection;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('buyers-experience')]
#[CoversClass(SalesChannelContextFactory::class)]
class SalesChannelContextFactoryTest extends TestCase
{
    public function testCustomerPaymentMethodIsOnlyUsedIfActive(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());

        $basePaymentMethod = new PaymentMethodEntity();
        $basePaymentMethod->setId(Uuid::randomHex());

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setLastPaymentMethodId(Uuid::randomHex());
        $customer->setDefaultBillingAddressId(Uuid::randomHex());
        $customer->setDefaultShippingAddressId(Uuid::randomHex());
        $customer->setGroupId(Uuid::randomHex());

        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setFactor(1);

        $billingAddress = new CustomerAddressEntity();
        $billingAddress->setId($customer->getDefaultBillingAddressId());
        $shippingAddress = new CustomerAddressEntity();
        $shippingAddress->setId($customer->getDefaultShippingAddressId());
        $shippingAddress->setCountry($country);
        $addresses = new CustomerAddressCollection([$billingAddress, $shippingAddress]);

        $baseContext = new BaseContext(
            Context::createDefaultContext(new SalesChannelApiSource($salesChannel->getId())),
            $salesChannel,
            $currency,
            new CustomerGroupEntity(),
            new TaxCollection(),
            $basePaymentMethod,
            new ShippingMethodEntity(),
            new ShippingLocation($country, null, null),
            new CashRoundingConfig(2, 0.01, true),
            new CashRoundingConfig(2, 0.01, true),
        );

        $paymentMethodRepository = new StaticEntityRepository(
            [
                static function (Criteria $criteria, Context $context) use ($baseContext) {
                    static::assertCount(2, $criteria->getFilters());
                    static::assertEquals([
                        new EqualsFilter('active', 1),
                        new EqualsFilter('salesChannels.id', $baseContext->getSalesChannel()->getId()),
                    ], $criteria->getFilters());

                    return new EntitySearchResult(
                        PaymentMethodDefinition::ENTITY_NAME,
                        0,
                        new PaymentMethodCollection(),
                        null,
                        $criteria,
                        $context
                    );
                },
            ],
            new PaymentMethodDefinition(),
        );

        $customerRepository = new StaticEntityRepository(
            [
                static function (Criteria $criteria, Context $context) use ($customer) {
                    return new EntitySearchResult(
                        CustomerDefinition::ENTITY_NAME,
                        1,
                        new CustomerCollection([$customer]),
                        null,
                        $criteria,
                        $context
                    );
                },
            ],
            new CustomerDefinition(),
        );

        $addressRepository = new StaticEntityRepository(
            [
                static function (Criteria $criteria, Context $context) use ($addresses) {
                    return new EntitySearchResult(
                        CustomerAddressDefinition::ENTITY_NAME,
                        2,
                        $addresses,
                        null,
                        $criteria,
                        $context
                    );
                },
            ],
            new CustomerAddressDefinition(),
        );

        $options = [
            SalesChannelContextService::CUSTOMER_ID => $customer->getId(),
        ];

        $baseContextFactory = $this->createMock(AbstractBaseContextFactory::class);
        $baseContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($salesChannel->getId(), $options)
            ->willReturn($baseContext);

        $factory = new SalesChannelContextFactory(
            $customerRepository,
            $this->createMock(EntityRepository::class),
            $addressRepository,
            $paymentMethodRepository,
            $this->createMock(TaxDetector::class),
            [],
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(EntityRepository::class),
            $baseContextFactory,
        );

        $generatedContext = $factory->create(Uuid::randomHex(), $salesChannel->getId(), $options);
        static::assertSame($generatedContext->getPaymentMethod(), $baseContext->getPaymentMethod());
    }
}
