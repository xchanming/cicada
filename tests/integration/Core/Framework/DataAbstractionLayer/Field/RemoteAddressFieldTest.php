<?php declare(strict_types=1);

namespace Cicada\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\SalesChannel\AccountService;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Order\OrderStates;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Cicada\Core\Framework\DataAbstractionLayer\Field\IntField;
use Cicada\Core\Framework\DataAbstractionLayer\Field\RemoteAddressField;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\RemoteAddressFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Cicada\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Cicada\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Cicada\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Cicada\Core\Framework\Feature;
use Cicada\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\System\SystemConfig\SystemConfigService;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * @internal
 */
class RemoteAddressFieldTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testRemoteAddressSerializerInvalidField(): void
    {
        $serializer = $this->getSerializer();
        $data = new KeyValuePair('remoteAddress', null, false);

        $this->expectException(DataAbstractionLayerException::class);
        $serializer->encode(
            (new IntField('remote_address', 'remoteAddress'))->addFlags(new ApiAware()),
            EntityExistence::createEmpty(),
            $data,
            $this->getWriteParameterBagMock()
        )->current();
    }

    public function testRemoteAddressSerializerValidField(): void
    {
        $serializer = $this->getSerializer();
        $data = new KeyValuePair('remoteAddress', '127.0.0.1', false);

        $curr = $serializer->encode(
            $this->getRemoteAddressField(),
            EntityExistence::createEmpty(),
            $data,
            $this->getWriteParameterBagMock()
        )->current();

        static::assertNotNull($curr);
    }

    public function testRemoteAddressSerializerAnonymize(): void
    {
        $this->setConfig();

        $remoteAddress = '127.0.0.1';
        $orderId = $this->createOrderWithRemoteAddress($remoteAddress);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));

        $orderCustomer = static::getContainer()->get('order_customer.repository')
            ->search($criteria, Context::createDefaultContext())
            ->first();

        static::assertInstanceOf(OrderCustomerEntity::class, $orderCustomer);
        static::assertNotSame($remoteAddress, $orderCustomer->getRemoteAddress());
        static::assertSame(IpUtils::anonymize($remoteAddress), $orderCustomer->getRemoteAddress());
    }

    public function testRemoteAddressSerializerNoAnonymize(): void
    {
        $this->setConfig(true);

        $remoteAddress = '127.0.0.1';
        $orderId = $this->createOrderWithRemoteAddress($remoteAddress);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));

        $orderCustomer = static::getContainer()->get('order_customer.repository')
            ->search($criteria, Context::createDefaultContext())
            ->first();

        static::assertInstanceOf(OrderCustomerEntity::class, $orderCustomer);
        static::assertSame($remoteAddress, $orderCustomer->getRemoteAddress());
    }

    public function testSetRemoteAddressByLogin(): void
    {
        $this->setConfig();

        $customerId = $this->createCustomer();

        static::getContainer()->get(AccountService::class)
            ->loginByCredentials('test@example.com', '12345678', $this->createSalesChannelContext());

        $criteria = new Criteria([$customerId]);

        $customer = static::getContainer()->get('customer.repository')
            ->search($criteria, Context::createDefaultContext())
            ->first();

        static::assertInstanceOf(CustomerEntity::class, $customer);
        static::assertNotSame('127.0.0.1', $customer->getRemoteAddress());
        static::assertSame(IpUtils::anonymize('127.0.0.1'), $customer->getRemoteAddress());
    }

    private function setConfig(bool $value = false): void
    {
        static::getContainer()->get(SystemConfigService::class)
            ->set('core.loginRegistration.customerIpAddressesNotAnonymously', $value);
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $salesChannelContextFactory = static::getContainer()->get(SalesChannelContextFactory::class);

        return $salesChannelContextFactory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    private function createOrderWithRemoteAddress(string $remoteAddress): string
    {
        $orderId = Uuid::randomHex();
        $addressId = Uuid::randomHex();
        $stateId = static::getContainer()->get(InitialStateIdLoader::class)->get(OrderStates::STATE_MACHINE);

        $customerId = $this->createCustomer();

        $order = [
            'id' => $orderId,
            'itemRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'totalRounding' => json_decode(json_encode(new CashRoundingConfig(2, 0.01, true), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR),
            'orderDateTime' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'price' => new CartPrice(10, 10, 10, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET),
            'shippingCosts' => new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()),
            'orderCustomer' => [
                'customerId' => $customerId,
                'email' => 'test@example.com',
                'salutationId' => $this->getValidSalutationId(),
                'name' => 'Max',
                'remoteAddress' => $remoteAddress,
            ],
            'stateId' => $stateId,
            'paymentMethodId' => $this->getValidPaymentMethodId(),
            'currencyId' => Defaults::CURRENCY,
            'currencyFactor' => 1.0,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'billingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                    'countryId' => $this->getValidCountryId(),
                ],
            ],
            'lineItems' => [],
            'deliveries' => [],
            'context' => '{}',
            'payload' => '{}',
        ];

        static::getContainer()->get('order.repository')->upsert([$order], Context::createDefaultContext());

        return $orderId;
    }

    private function createCustomer(): string
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'customerNumber' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'name' => 'Max',
            'email' => 'test@example.com',
            'password' => TestDefaults::HASHED_PASSWORD,
            'groupId' => TestDefaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'remoteAddress' => '127.0.0.1',
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'name' => 'Max',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        if (!Feature::isActive('v6.7.0.0')) {
            $customer['defaultPaymentMethodId'] = $this->getValidPaymentMethodId();
        }

        static::getContainer()->get('customer.repository')->upsert([$customer], Context::createDefaultContext());

        return $customerId;
    }

    private function getSerializer(): RemoteAddressFieldSerializer
    {
        return static::getContainer()->get(RemoteAddressFieldSerializer::class);
    }

    private function getWriteParameterBagMock(): WriteParameterBag
    {
        $mockBuilder = $this->getMockBuilder(WriteParameterBag::class);
        $mockBuilder->disableOriginalConstructor();

        return $mockBuilder->getMock();
    }

    private function getRemoteAddressField(): RemoteAddressField
    {
        return new RemoteAddressField('remote_address', 'remoteAddress');
    }
}
