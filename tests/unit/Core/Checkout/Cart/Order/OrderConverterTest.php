<?php declare(strict_types=1);

namespace Cicada\Tests\Unit\Core\Checkout\Cart\Order;

use Cicada\Core\Checkout\Cart\Cart;
use Cicada\Core\Checkout\Cart\CartException;
use Cicada\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Cicada\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Cicada\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Cicada\Core\Checkout\Cart\Event\SalesChannelContextAssembledEvent;
use Cicada\Core\Checkout\Cart\LineItem\LineItem;
use Cicada\Core\Checkout\Cart\Order\CartConvertedEvent;
use Cicada\Core\Checkout\Cart\Order\IdStruct;
use Cicada\Core\Checkout\Cart\Order\LineItemDownloadLoader;
use Cicada\Core\Checkout\Cart\Order\OrderConversionContext;
use Cicada\Core\Checkout\Cart\Order\OrderConverter;
use Cicada\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Cicada\Core\Checkout\Cart\Price\Struct\CartPrice;
use Cicada\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Cicada\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Cicada\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Cicada\Core\Checkout\Customer\CustomerEntity;
use Cicada\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderDeliveryPosition\OrderDeliveryPositionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderDeliveryPosition\OrderDeliveryPositionEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItemDownload\OrderLineItemDownloadCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderLineItemDownload\OrderLineItemDownloadEntity;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Cicada\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Cicada\Core\Checkout\Order\OrderDefinition;
use Cicada\Core\Checkout\Order\OrderEntity;
use Cicada\Core\Checkout\Order\OrderException;
use Cicada\Core\Checkout\Payment\PaymentMethodEntity;
use Cicada\Core\Checkout\Shipping\ShippingMethodEntity;
use Cicada\Core\Content\Product\Aggregate\ProductDownload\ProductDownloadEntity;
use Cicada\Core\Content\Product\State;
use Cicada\Core\Content\Rule\RuleCollection;
use Cicada\Core\Content\Rule\RuleEntity;
use Cicada\Core\Defaults;
use Cicada\Core\Framework\CicadaHttpException;
use Cicada\Core\Framework\Context;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;
use Cicada\Core\Framework\DataAbstractionLayer\EntityRepository;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Flag\RuleAreas;
use Cicada\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Cicada\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Cicada\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Cicada\Core\Framework\Test\TestCaseHelper\ReflectionHelper;
use Cicada\Core\Framework\Uuid\Uuid;
use Cicada\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Cicada\Core\System\Country\CountryEntity;
use Cicada\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Cicada\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Cicada\Core\System\SalesChannel\Context\SalesChannelContextService;
use Cicada\Core\System\SalesChannel\SalesChannelContext;
use Cicada\Core\System\SalesChannel\SalesChannelEntity;
use Cicada\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Cicada\Core\System\StateMachine\Loader\InitialStateIdLoader;
use Cicada\Core\Test\Generator;
use Cicada\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Cicada\Core\Test\TestDefaults;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[CoversClass(OrderConverter::class)]
class OrderConverterTest extends TestCase
{
    private EventDispatcher $eventDispatcher;

    private CashRoundingConfig $cashRoundingConfig;

    private OrderConverter $orderConverter;

    protected function setUp(): void
    {
        $this->cashRoundingConfig = new CashRoundingConfig(2, 0.01, true);
        $this->eventDispatcher = new EventDispatcher();
        $this->orderConverter = $this->getOrderConverter();
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('assembleSalesChannelContextData')]
    public function testAssembleSalesChannelContext(string $exceptionClass, string $manipulateOrder = ''): void
    {
        if ($exceptionClass !== '') {
            $this->expectException($exceptionClass);
        }

        $orderAddressRepositorySearchResult = [];
        if ($exceptionClass !== AddressNotFoundException::class) {
            $orderAddressRepositorySearchResult = [$this->getOrderAddress()];
        }

        $orderConverter = $this->getOrderConverter(
            [$this->getCustomer(false)],
            $orderAddressRepositorySearchResult,
            function (string $randomId, string $salesChannelId, array $options): SalesChannelContext {
                $expectedOptions = [
                    SalesChannelContextService::CURRENCY_ID => 'order-currency-id',
                    SalesChannelContextService::LANGUAGE_ID => 'order-language-id',
                    SalesChannelContextService::CUSTOMER_ID => 'customer-id',
                    SalesChannelContextService::CUSTOMER_GROUP_ID => 'customer-group-id',
                    SalesChannelContextService::PERMISSIONS => OrderConverter::ADMIN_EDIT_ORDER_PERMISSIONS,
                    SalesChannelContextService::VERSION_ID => Defaults::LIVE_VERSION,
                    SalesChannelContextService::COUNTRY_STATE_ID => 'order-address-country-state-id',
                    SalesChannelContextService::SHIPPING_METHOD_ID => 'order-delivery-shipping-method-id',
                    SalesChannelContextService::PAYMENT_METHOD_ID => 'order-transaction-payment-method-id',
                ];
                static::assertSame($expectedOptions, $options);

                return $this->getSalesChannelContext(true);
            }
        );

        $orderEntity = $this->getOrder($manipulateOrder);
        $orderConverter->assembleSalesChannelContext($orderEntity, Context::createDefaultContext());
    }

    /**
     * @return list<list<string>>
     */
    public static function assembleSalesChannelContextData(): array
    {
        return [
            [
                OrderException::class,
                'order-no-transactions',
            ],
            [
                OrderException::class,
                'order-no-order-customer',
            ],
            [
                AddressNotFoundException::class,
            ],
            [
                '',
            ],
        ];
    }

    public function testConvertToOrderWithoutDeliveries(): void
    {
        $cart = $this->getCart();
        $result = $this->orderConverter->convertToOrder($cart, $this->getSalesChannelContext(true), new OrderConversionContext());

        // unset uncheckable ids
        unset(
            $result['id'],
            $result['billingAddressId'],
            $result['deepLinkCode'],
            $result['orderDateTime'],
            $result['stateId'],
            $result['languageId'],
        );
        for ($i = 0; $i < (is_countable($result['lineItems']) ? \count($result['lineItems']) : 0); ++$i) {
            unset($result['lineItems'][$i]['id']);
        }

        for ($i = 0; $i < (is_countable($result['addresses']) ? \count($result['addresses']) : 0); ++$i) {
            unset($result['addresses'][$i]['id']);
        }

        $expected = $this->getExpectedConvertToOrder();
        $expected['deliveries'] = [];

        $expectedJson = \json_encode($expected, \JSON_THROW_ON_ERROR);
        static::assertIsString($expectedJson);
        $actual = \json_encode($result, \JSON_THROW_ON_ERROR);
        static::assertIsString($actual);

        // As json to avoid classes
        static::assertJsonStringEqualsJsonString($expectedJson, $actual);
    }

    public function testConvertToOrderShouldNotContainDeliveriesWithNoAddress(): void
    {
        $cart = $this->getCart();

        $cart->setDeliveries(
            $this->getDeliveryCollection(true)
        );

        $orderConversionContext = new OrderConversionContext();
        $orderConversionContext->setIncludeDeliveries(false);

        $result = $this->orderConverter->convertToOrder($cart, $this->getSalesChannelContext(true), $orderConversionContext);

        static::assertEmpty($result['deliveries']);
    }

    public function testConvertToOrderShouldNotContainDeliveriesWithNoAddressButHaveOriginalAddressId(): void
    {
        $cart = $this->getCart();

        $cart->setDeliveries(
            $this->getDeliveryCollection(true)
        );

        foreach ($cart->getDeliveries() as $delivery) {
            $delivery->addExtension(OrderConverter::ORIGINAL_ADDRESS_ID, new IdStruct('original-address-id'));
            $delivery->addExtension(OrderConverter::ORIGINAL_ADDRESS_VERSION_ID, new IdStruct('original-address-version-id'));
        }

        $orderConversionContext = new OrderConversionContext();
        $orderConversionContext->setIncludeDeliveries(true);

        $result = $this->orderConverter->convertToOrder($cart, $this->getSalesChannelContext(true), $orderConversionContext);

        static::assertNotEmpty($result['deliveries']);
    }

    public function testConvertToOrderWithDeliveries(): void
    {
        $cart = $this->getCart();
        $cart->setDeliveries($this->getDeliveryCollection());

        $result = $this->orderConverter->convertToOrder($cart, $this->getSalesChannelContext(true), new OrderConversionContext());

        // unset uncheckable ids
        unset(
            $result['id'],
            $result['billingAddressId'],
            $result['deepLinkCode'],
            $result['orderDateTime'],
            $result['stateId'],
            $result['languageId'],
        );
        for ($i = 0; $i < (is_countable($result['lineItems']) ? \count($result['lineItems']) : 0); ++$i) {
            unset($result['lineItems'][$i]['id']);
        }

        for ($i = 0; $i < (is_countable($result['deliveries']) ? \count($result['deliveries']) : 0); ++$i) {
            unset(
                $result['deliveries'][$i]['shippingOrderAddress']['id'],
                $result['deliveries'][$i]['shippingDateEarliest'],
                $result['deliveries'][$i]['shippingDateLatest'],
            );
        }

        $expected = $this->getExpectedConvertToOrder();
        unset($expected['addresses']);
        $expected['shippingCosts']['unitPrice'] = 1;
        $expected['shippingCosts']['totalPrice'] = 1;

        $expectedJson = \json_encode($expected, \JSON_THROW_ON_ERROR);
        static::assertIsString($expectedJson);
        $actual = \json_encode($result, \JSON_THROW_ON_ERROR);
        static::assertIsString($actual);
        // As json to avoid classes
        static::assertJsonStringEqualsJsonString($expectedJson, $actual);
    }

    /**
     * @param class-string<\Throwable> $exceptionClass
     */
    #[DataProvider('convertToOrderExceptionsData')]
    public function testConvertToOrderExceptions(string $exceptionClass, bool $loginCustomer = true, bool $conversionIncludeCustomer = true): void
    {
        if ($exceptionClass !== '') {
            $this->expectException($exceptionClass);
        }

        $cart = $this->getCart();
        $cart->setDeliveries(
            $this->getDeliveryCollection(
                $exceptionClass === OrderException::class
            )
        );

        $conversionContext = new OrderConversionContext();
        $conversionContext->setIncludeCustomer($conversionIncludeCustomer);

        $salesChannelContext = $this->getSalesChannelContext(
            $loginCustomer,
            $exceptionClass === AddressNotFoundException::class
        );

        $result = $this->orderConverter->convertToOrder($cart, $salesChannelContext, $conversionContext);

        // unset uncheckable ids
        unset(
            $result['id'],
            $result['billingAddressId'],
            $result['deepLinkCode'],
            $result['orderDateTime'],
            $result['stateId'],
            $result['languageId'],
        );
        for ($i = 0; $i < (is_countable($result['lineItems']) ? \count($result['lineItems']) : 0); ++$i) {
            unset($result['lineItems'][$i]['id']);
        }

        for ($i = 0; $i < (is_countable($result['deliveries']) ? \count($result['deliveries']) : 0); ++$i) {
            unset(
                $result['deliveries'][$i]['shippingOrderAddress']['id'],
                $result['deliveries'][$i]['shippingDateEarliest'],
                $result['deliveries'][$i]['shippingDateLatest'],
            );
        }

        $expected = $this->getExpectedConvertToOrder();
        unset($expected['addresses']);
        $expected['shippingCosts']['unitPrice'] = 1;
        $expected['shippingCosts']['totalPrice'] = 1;

        $expectedJson = \json_encode($expected, \JSON_THROW_ON_ERROR);
        static::assertIsString($expectedJson);
        $actual = \json_encode($result, \JSON_THROW_ON_ERROR);
        static::assertIsString($actual);
        // As json to avoid classes
        static::assertJsonStringEqualsJsonString($expectedJson, $actual);
    }

    /**
     * @return list<array{0: class-string<CicadaHttpException>, 1?: false, 2?: false}>
     */
    public static function convertToOrderExceptionsDataWithDisabledFeatures(): array
    {
        return [
            [
                AddressNotFoundException::class,
            ],
            [
                DeliveryWithoutAddressException::class,
            ],
            [
                CartException::class,
                false,
            ],
            [
                CartException::class,
                false,
                false,
            ],
        ];
    }

    /**
     * @return list<array{0: class-string<CicadaHttpException>, 1?: false, 2?: false}>
     */
    public static function convertToOrderExceptionsData(): array
    {
        return [
            [
                OrderException::class,
            ],
            [
                CartException::class,
                false,
            ],
            [
                CartException::class,
                false,
                false,
            ],
        ];
    }

    public function testConvertToCart(): void
    {
        $result = $this->orderConverter->convertToCart($this->getOrder(), Context::createDefaultContext());
        $result = \json_encode($result, \JSON_THROW_ON_ERROR);
        static::assertIsString($result);
        $result = \json_decode($result, true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotFalse($result);

        // unset uncheckable ids
        unset(
            $result['extensions']['originalId'],
            $result['token'],
            $result['errorHash']
        );
        for ($i = 0; $i < (is_countable($result['lineItems']) ? \count($result['lineItems']) : 0); ++$i) {
            unset(
                $result['lineItems'][$i]['extensions']['originalId'],
                $result['lineItems'][$i]['uniqueIdentifier'],
            );
        }

        for ($i = 0; $i < (is_countable($result['deliveries']) ? \count($result['deliveries']) : 0); ++$i) {
            unset($result['deliveries'][$i]['deliveryDate']);
            for ($f = 0; $f < (is_countable($result['deliveries'][$i]['positions']) ? \count($result['deliveries'][$i]['positions']) : 0); ++$f) {
                unset(
                    $result['deliveries'][$i]['positions'][$f]['deliveryDate'],
                    $result['deliveries'][$i]['positions'][$f]['lineItem']['uniqueIdentifier'],
                );
            }
        }

        $expected = $this->getExpectedConvertToCart();

        static::assertEquals($expected, $result);
    }

    #[DataProvider('convertToCartManipulatedOrderData')]
    public function testConvertToCartManipulatedOrder(string $manipulateOrder = ''): void
    {
        $order = $this->getOrder($manipulateOrder);

        $result = $this->orderConverter->convertToCart($order, Context::createDefaultContext());
        $result = \json_encode($result, \JSON_THROW_ON_ERROR);
        static::assertIsString($result);
        $result = \json_decode($result, true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotFalse($result);

        // unset uncheckable ids
        unset(
            $result['extensions']['originalId'],
            $result['token'],
            $result['errorHash']
        );
        for ($i = 0; $i < (is_countable($result['lineItems']) ? \count($result['lineItems']) : 0); ++$i) {
            unset(
                $result['lineItems'][$i]['extensions']['originalId'],
                $result['lineItems'][$i]['uniqueIdentifier'],
            );
        }

        for ($i = 0; $i < (is_countable($result['deliveries']) ? \count($result['deliveries']) : 0); ++$i) {
            unset($result['deliveries'][$i]['deliveryDate']);
            for ($f = 0; $f < (is_countable($result['deliveries'][$i]['positions']) ? \count($result['deliveries'][$i]['positions']) : 0); ++$f) {
                unset($result['deliveries'][$i]['positions'][$f]['deliveryDate']);
            }
        }

        $expected = $this->getExpectedConvertToCart();
        $expected['deliveries'] = [];

        static::assertEquals($expected, $result);
    }

    /**
     * @return array<array<string>>
     */
    public static function convertToCartManipulatedOrderData(): array
    {
        return [
            [
                'order-no-order-deliveries',
            ],
            [
                'order-delivery-no-position',
            ],
            [
                'order-delivery-no-shipping-method',
            ],
        ];
    }

    #[DataProvider('convertToCartExceptionsData')]
    public function testConvertToCartExceptions(string $manipulateOrder): void
    {
        $this->expectException(OrderException::class);

        $order = $this->getOrder($manipulateOrder);

        $result = $this->orderConverter->convertToCart($order, Context::createDefaultContext());
        $result = \json_encode($result, \JSON_THROW_ON_ERROR);
        static::assertIsString($result);
        $result = \json_decode($result, true, 512, \JSON_THROW_ON_ERROR);
        static::assertNotFalse($result);

        // unset uncheckable ids
        unset(
            $result['extensions']['originalId'],
            $result['token'],
        );
        for ($i = 0; $i < (is_countable($result['lineItems']) ? \count($result['lineItems']) : 0); ++$i) {
            unset($result['lineItems'][$i]['extensions']['originalId']);
        }

        for ($i = 0; $i < (is_countable($result['deliveries']) ? \count($result['deliveries']) : 0); ++$i) {
            unset($result['deliveries'][$i]['deliveryDate']);
            for ($f = 0; $f < (is_countable($result['deliveries'][$i]['positions']) ? \count($result['deliveries'][$i]['positions']) : 0); ++$f) {
                unset($result['deliveries'][$i]['positions'][$f]['deliveryDate']);
            }
        }

        static::assertSame($this->getExpectedConvertToCart(), $result);
    }

    /**
     * @return array<array<string>>
     */
    public static function convertToCartExceptionsData(): array
    {
        return [
            [
                'order-no-line-items',
            ],
            [
                'order-no-deliveries',
            ],
            [
                'order-no-order-number',
            ],
        ];
    }

    public function testEventsAreCalled(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->willReturn(static::isInstanceOf(CartConvertedEvent::class));

        $orderConverter = $this->getOrderConverter(
            null,
            null,
            null,
            $dispatcher
        );

        $orderConverter->convertToOrder($this->getCart(), $this->getSalesChannelContext(true), new OrderConversionContext());
    }

    public function testConvertionWithDownloads(): void
    {
        $cart = $this->orderConverter->convertToCart($this->getOrder('order-add-line-item-download'), Context::createDefaultContext());
        $lineItem = $cart->getLineItems()->first();

        static::assertNotNull($lineItem);
        static::assertInstanceOf(LineItem::class, $lineItem);
        $collection = $lineItem->getExtensionOfType(OrderConverter::ORIGINAL_DOWNLOADS, OrderLineItemDownloadCollection::class);
        static::assertInstanceOf(OrderLineItemDownloadCollection::class, $collection);
        static::assertCount(1, $collection);

        $cart = $this->getCart();
        $cart->getLineItems()->clear();
        $lineItemA = (new LineItem('line-item-label-1', 'line-item-label-1', Uuid::randomHex()))
            ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
            ->setLabel('line-item-label-1')
            ->setStates([State::IS_DOWNLOAD]);
        $lineItemA->addExtension(OrderConverter::ORIGINAL_DOWNLOADS, $collection);
        $lineItemB = (new LineItem('line-item-label-2', 'line-item-label-2', Uuid::randomHex()))
            ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
            ->setLabel('line-item-label-2')
            ->setStates([State::IS_DOWNLOAD]);
        $cart->add($lineItemA);
        $cart->add($lineItemB);

        $order = $this->orderConverter->convertToOrder($cart, $this->getSalesChannelContext(true), new OrderConversionContext());

        static::assertArrayHasKey('lineItems', $order);
        static::assertIsArray($order['lineItems']);
        static::assertCount(2, $order['lineItems']);

        $lineItemA = \is_array($order['lineItems'][0]) ? $order['lineItems'][0] : [];
        $lineItemB = \is_array($order['lineItems'][1]) ? $order['lineItems'][1] : [];

        static::assertIsArray($lineItemA['downloads']);
        static::assertArrayHasKey('id', $lineItemA['downloads'][0]);
        static::assertArrayNotHasKey('mediaId', $lineItemA['downloads'][0]);
        static::assertIsArray($lineItemB['downloads']);
        static::assertArrayNotHasKey('id', $lineItemB['downloads'][0]);
        static::assertArrayHasKey('mediaId', $lineItemB['downloads'][0]);
        static::assertArrayHasKey('position', $lineItemB['downloads'][0]);
    }

    public function testAssembleSalesChannelContextEventIsDispatched(): void
    {
        $order = $this->getOrder();
        $salesChannelContext = $this->getSalesChannelContext(true);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->expects(static::once())
            ->method('dispatch')
            ->with(static::callback(static function (SalesChannelContextAssembledEvent $event) use ($order): bool {
                static::assertSame($order, $event->getOrder());

                return true;
            }));

        $address = new OrderAddressEntity();
        $address->setId('order-address-id');
        $address->setUniqueIdentifier('order-address-id');

        $addresses = new OrderAddressCollection([$address]);

        $addressRepository = $this->createMock(EntityRepository::class);
        $addressRepository
            ->expects(static::once())
            ->method('search')
            ->willReturn(new EntitySearchResult(
                'order_address',
                1,
                $addresses,
                null,
                new Criteria(),
                $salesChannelContext->getContext()
            ));

        /** @var StaticEntityRepository<RuleCollection> $ruleRepository */
        $ruleRepository = new StaticEntityRepository([new RuleCollection()]);

        $converter = new OrderConverter(
            $this->createMock(EntityRepository::class),
            $this->createMock(SalesChannelContextFactory::class),
            $dispatcher,
            $this->createMock(NumberRangeValueGeneratorInterface::class),
            $this->createMock(OrderDefinition::class),
            $addressRepository,
            $this->createMock(InitialStateIdLoader::class),
            $this->createMock(LineItemDownloadLoader::class),
            $ruleRepository,
        );

        $converter->assembleSalesChannelContext($order, $salesChannelContext->getContext());
    }

    private function getSalesChannelContext(bool $loginCustomer, bool $customerWithoutBillingAddress = false): SalesChannelContext
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannel->setLanguageId(Defaults::LANGUAGE_SYSTEM);

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId('payment-method-id');

        $salesChannelContext = Generator::generateSalesChannelContext(
            salesChannel: $salesChannel,
            paymentMethod: $paymentMethod,
            customer: $loginCustomer ? $this->getCustomer($customerWithoutBillingAddress) : null,
            itemRounding: $this->cashRoundingConfig,
            totalRounding: $this->cashRoundingConfig,
            areaRuleIds: [RuleAreas::PAYMENT_AREA => ['rule-id']],
            overrides: $loginCustomer ? [] : ['customer' => null]
        );

        $salesChannelContext->setRuleIds(['order-rule-id-1', 'order-rule-id-2']);

        return $salesChannelContext;
    }

    private function getCart(): Cart
    {
        $cart = new Cart('cart-token');
        $cart->add(
            (new LineItem('line-item-id-1', LineItem::PRODUCT_LINE_ITEM_TYPE))
                ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
                ->setLabel('line-item-label-1')
        )->add(
            (new LineItem('line-item-id-2', LineItem::PRODUCT_LINE_ITEM_TYPE))
                ->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()))
                ->setLabel('line-item-label-2')
        );

        return $cart;
    }

    private function getOrder(string $toManipulate = ''): OrderEntity
    {
        // Order line items
        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setIdentifier('order-line-item-identifier');
        $orderLineItem->setId('order-line-item-id');
        $orderLineItem->setQuantity(1);
        $orderLineItem->setType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        $orderLineItem->setLabel('order-line-item-label');
        $orderLineItem->setGood(true);
        $orderLineItem->setRemovable(false);
        $orderLineItem->setStackable(true);

        if ($toManipulate === 'order-add-line-item-download') {
            $orderLineItemDownload = new OrderLineItemDownloadEntity();
            $orderLineItemDownload->setId(Uuid::randomHex());
            $orderLineItemDownload->setMediaId(Uuid::randomHex());

            $orderLineItemDownloadCollection = new OrderLineItemDownloadCollection();
            $orderLineItemDownloadCollection->add($orderLineItemDownload);
            $orderLineItem->setDownloads($orderLineItemDownloadCollection);
        }

        $orderLineItemCollection = new OrderLineItemCollection();
        $orderLineItemCollection->add($orderLineItem);

        // Order delivery position
        $orderDeliveryPositionCollection = new OrderDeliveryPositionCollection();
        $orderDeliveryPosition = new OrderDeliveryPositionEntity();
        $orderDeliveryPosition->setId('order-delivery-position-id-1');
        $orderDeliveryPosition->setOrderLineItem($orderLineItem);
        $orderDeliveryPosition->setPrice(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $orderDeliveryPositionCollection->add($orderDeliveryPosition);

        // Order delivery
        $orderDeliveryCollection = new OrderDeliveryCollection();
        $orderDelivery = new OrderDeliveryEntity();
        $orderDelivery->setId('order-delivery-id');
        $orderDelivery->setShippingDateEarliest(new \DateTimeImmutable());
        $orderDelivery->setShippingDateLatest(new \DateTimeImmutable());
        $orderDelivery->setShippingMethodId('order-delivery-shipping-method-id');
        $orderAddress = $this->getOrderAddress();
        $orderDelivery->setShippingOrderAddress($orderAddress);
        $orderDelivery->setShippingOrderAddressId($orderAddress->getId());
        static::assertIsString($orderAddress->getVersionId());
        $orderDelivery->setShippingOrderAddressVersionId($orderAddress->getVersionId());
        $orderDelivery->setShippingCosts(new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection()));
        if ($toManipulate !== 'order-delivery-no-shipping-method') {
            $orderDelivery->setShippingMethod(new ShippingMethodEntity());
        }
        if ($toManipulate !== 'order-delivery-no-position') {
            $orderDelivery->setPositions($orderDeliveryPositionCollection);
        }
        if ($toManipulate !== 'order-no-order-deliveries') {
            $orderDeliveryCollection->add($orderDelivery);
        }

        // Transactions
        $orderTransactionCollection = new OrderTransactionCollection();

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId('order-transaction-id');
        $orderTransaction->setPaymentMethodId('order-transaction-payment-method-id');
        $stateMachineState = new StateMachineStateEntity();
        $stateMachineState->setId('state-machine-state-id');
        $stateMachineState->setTechnicalName('state-machine-state-technical-name');
        $orderTransaction->setStateMachineState($stateMachineState);

        $orderTransactionCancelled = new OrderTransactionEntity();
        $orderTransactionCancelled->setId('order-transaction-cancelled-id');
        $orderTransactionCancelled->setPaymentMethodId('order-transaction-cancelled-payment-method-id');
        $stateMachineStateCancelled = new StateMachineStateEntity();
        $stateMachineStateCancelled->setId('state-machine-cancelled-state-id');
        $stateMachineStateCancelled->setTechnicalName('cancelled');
        $orderTransactionCancelled->setStateMachineState($stateMachineStateCancelled);

        $orderTransactionFailed = new OrderTransactionEntity();
        $orderTransactionFailed->setId('order-transaction-failed-id');
        $orderTransactionFailed->setPaymentMethodId('order-transaction-failed-payment-method-id');
        $stateMachineStateFailed = new StateMachineStateEntity();
        $stateMachineStateFailed->setId('state-machine-failed-state-id');
        $stateMachineStateFailed->setTechnicalName('failed');
        $orderTransactionFailed->setStateMachineState($stateMachineStateFailed);

        $orderTransactionCollection->add($orderTransactionCancelled);
        $orderTransactionCollection->add($orderTransaction);
        $orderTransactionCollection->add($orderTransactionFailed);

        // Cart price
        $cartPrice = new CartPrice(19.5, 19.5, 19.5, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_FREE);

        // Order entity
        $order = new OrderEntity();
        $order->setPrice($cartPrice);
        $order->setId(Uuid::randomHex());
        $order->setBillingAddressId('order-address-id');
        $order->setCurrencyId('order-currency-id');
        $order->setLanguageId('order-language-id');
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setTotalRounding($this->cashRoundingConfig);
        $order->setItemRounding($this->cashRoundingConfig);
        $order->setRuleIds(['order-rule-id-1', 'order-rule-id-2']);
        $order->setTaxStatus(CartPrice::TAX_STATE_FREE);

        if ($toManipulate !== 'order-no-order-customer') {
            $order->setOrderCustomer($this->getOrderCustomer());
        }
        if ($toManipulate !== 'order-no-transactions') {
            $order->setTransactions($orderTransactionCollection);
        }
        if ($toManipulate !== 'order-no-line-items') {
            $order->setLineItems($orderLineItemCollection);
        }
        if ($toManipulate !== 'order-no-deliveries') {
            $order->setDeliveries($orderDeliveryCollection);
        }
        if ($toManipulate !== 'order-no-order-number') {
            $order->setOrderNumber('10000');
        }

        return $order;
    }

    /**
     * @param array<CustomerEntity>|null $customerRepositoryResultArray
     * @param array<OrderAddressEntity>|null $orderAddressRepositoryResultArray
     * @param callable(string, string, array<string, mixed>): SalesChannelContext|null $salesChannelContextFactoryCreateCallable
     */
    private function getOrderConverter(?array $customerRepositoryResultArray = null, ?array $orderAddressRepositoryResultArray = null, ?callable $salesChannelContextFactoryCreateCallable = null, ?EventDispatcherInterface $eventDispatcher = null): OrderConverter
    {
        // Setup classes for OrderConverter
        // Static
        $orderDefinition = new OrderDefinition();
        $initialStateIdLoader = $this->createMock(InitialStateIdLoader::class);
        $numberRangeValueGenerator = $this->createMock(NumberRangeValueGeneratorInterface::class);
        $numberRangeValueGenerator->method('getValue')->willReturn('10000');

        // Dynamic
        $salesChannelContextFactory = $this->createMock(AbstractSalesChannelContextFactory::class);
        if ($salesChannelContextFactoryCreateCallable !== null) {
            $salesChannelContextFactory->method('create')->willReturnCallback($salesChannelContextFactoryCreateCallable);
        }

        $customerRepository = $this->createMock(EntityRepository::class);
        if ($customerRepositoryResultArray !== null) {
            $customerRepository->method('search')->willReturn(
                new EntitySearchResult(
                    'customer',
                    1,
                    new EntityCollection($customerRepositoryResultArray),
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );
        }

        $orderAddressRepository = $this->createMock(EntityRepository::class);
        if ($orderAddressRepositoryResultArray !== null) {
            $orderAddressRepository->method('search')->willReturn(
                new EntitySearchResult(
                    'orderAddress',
                    1,
                    new EntityCollection($orderAddressRepositoryResultArray),
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                )
            );
        }

        $rule = new RuleEntity();
        $rule->setId('rule-id');
        $rule->setAreas([RuleAreas::PAYMENT_AREA]);
        /** @var StaticEntityRepository<RuleCollection> $ruleRepository */
        $ruleRepository = new StaticEntityRepository([new RuleCollection([$rule])]);

        $productDownload = new ProductDownloadEntity();
        $productDownload->setId(Uuid::randomHex());
        $productDownload->setMediaId(Uuid::randomHex());
        $productDownload->setPosition(0);
        $productDownloadRepository = $this->createMock(EntityRepository::class);
        $productDownloadRepository->method('search')->willReturnCallback(function (Criteria $criteria) use ($productDownload): EntitySearchResult {
            $filters = $criteria->getFilters();
            if (isset($filters[0]) && $filters[0] instanceof EqualsAnyFilter) {
                $value = ReflectionHelper::getPropertyValue($filters[0], 'value');
                $productDownload->setProductId($value[0] ?? null);
            }

            return new EntitySearchResult(
                'productDownload',
                1,
                new EntityCollection([$productDownload]),
                null,
                new Criteria(),
                Context::createDefaultContext()
            );
        });

        $lineItemDownloadLoader = new LineItemDownloadLoader($productDownloadRepository);

        return new OrderConverter(
            $customerRepository,
            $salesChannelContextFactory,
            $eventDispatcher ?? $this->eventDispatcher,
            $numberRangeValueGenerator,
            $orderDefinition,
            $orderAddressRepository,
            $initialStateIdLoader,
            $lineItemDownloadLoader,
            $ruleRepository,
        );
    }

    private function getCustomer(bool $withoutBillingAddress): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId('customer-id');
        $customer->setEmail('customer-email');
        $customer->setSalutationId('customer-salutation-id');
        $customer->setName('customer-first-name');
        $customer->setCustomerNumber('customer-number');
        $customer->setGroupId('customer-group-id');

        if (!$withoutBillingAddress) {
            $customer->setDefaultBillingAddress($this->getCustomerAddress());
        }

        return $customer;
    }

    private function getCustomerAddress(): CustomerAddressEntity
    {
        $address = new CustomerAddressEntity();
        $address->setId('billing-address-id');
        $address->setSalutationId('billing-address-salutation-id');
        $address->setName('billing-address-first-name');
        $address->setStreet('billing-address-street');
        $address->setZipcode('billing-address-zipcode');
        $address->setCity('billing-address-city');
        $address->setCountryId('billing-address-country-id');

        return $address;
    }

    private function getOrderCustomer(): OrderCustomerEntity
    {
        $customer = new OrderCustomerEntity();
        $customer->setId('order-customer-id');
        $customer->setCustomerId('customer-id');
        $customer->setEmail('order-customer-email');
        $customer->setSalutationId('order-customer-salutation-id');
        $customer->setName('order-customer-first-name');
        $customer->setCustomerNumber('order-customer-number');

        return $customer;
    }

    private function getOrderAddress(): OrderAddressEntity
    {
        $country = new CountryEntity();
        $country->setId('country-id');
        $country->setName('country-name');

        $countryState = new CountryStateEntity();
        $countryState->setId('country-state-id');
        $countryState->setName('country-state-name');

        $address = new OrderAddressEntity();
        $address->setId('order-address-id');
        $address->setVersionId('order-address-version-id');
        $address->setSalutationId('order-address-salutation-id');
        $address->setName('order-address-first-name');
        $address->setStreet('order-address-street');
        $address->setZipcode('order-address-zipcode');
        $address->setCity('order-address-city');
        $address->setCountryId('order-address-country-id');
        $address->setCountryStateId('order-address-country-state-id');
        $address->setCountry($country);
        $address->setCountryState($countryState);

        return $address;
    }

    private function getDeliveryCollection(bool $withoutAddress = false): DeliveryCollection
    {
        $country = new CountryEntity();
        $country->setId('country-id');
        $country->setName('country-name');

        $countryState = new CountryStateEntity();
        $countryState->setId('country-state-id');
        $countryState->setName('country-state-name');

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('shipping-method-id');

        $shippingLocation = new ShippingLocation($country, null, null);
        if (!$withoutAddress) {
            $shippingLocation = new ShippingLocation($country, $countryState, $this->getCustomerAddress());
        }

        $deliveryCollection = new DeliveryCollection();
        $delivery = new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(new \DateTimeImmutable(), new \DateTimeImmutable()),
            $shippingMethod,
            $shippingLocation,
            new CalculatedPrice(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection())
        );
        $deliveryCollection->add($delivery);

        return $deliveryCollection;
    }

    // Expectations
    /**
     * @return array<string, mixed>
     */
    private function getExpectedConvertToCart(): array
    {
        return [
            'extensions' => [
                'originalOrderNumber' => [
                    'extensions' => [],
                    'id' => '10000',
                ],
            ],
            'price' => [
                'netPrice' => 19.5,
                'totalPrice' => 19.5,
                'calculatedTaxes' => [],
                'taxRules' => [],
                'positionPrice' => 19.5,
                'taxStatus' => 'tax-free',
                'rawTotal' => 19.5,
                'extensions' => [],
            ],
            'lineItems' => [
                [
                    'payload' => [],
                    'id' => 'order-line-item-identifier',
                    'referencedId' => null,
                    'label' => 'order-line-item-label',
                    'quantity' => 1,
                    'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                    'priceDefinition' => null,
                    'price' => null,
                    'good' => true,
                    'description' => null,
                    'cover' => null,
                    'deliveryInformation' => null,
                    'children' => [],
                    'requirement' => null,
                    'removable' => false,
                    'stackable' => true,
                    'quantityInformation' => null,
                    'modified' => false,
                    'dataTimestamp' => null,
                    'dataContextHash' => null,
                    'extensions' => [],
                    'states' => [],
                    'modifiedByApp' => false,
                    'shippingCostAware' => true,
                ],
            ],
            'errors' => [],
            'deliveries' => [
                [
                    'positions' => [
                        [
                            'lineItem' => [
                                'payload' => [],
                                'id' => 'order-line-item-identifier',
                                'referencedId' => null,
                                'label' => 'order-line-item-label',
                                'quantity' => 1,
                                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                                'priceDefinition' => null,
                                'price' => null,
                                'good' => true,
                                'description' => null,
                                'cover' => null,
                                'deliveryInformation' => null,
                                'children' => [],
                                'requirement' => null,
                                'removable' => false,
                                'stackable' => true,
                                'quantityInformation' => null,
                                'modified' => false,
                                'dataTimestamp' => null,
                                'dataContextHash' => null,
                                'extensions' => [
                                    'originalId' => [
                                        'id' => 'order-line-item-id',
                                        'extensions' => [],
                                    ],
                                ],
                                'states' => [],
                                'modifiedByApp' => false,
                                'shippingCostAware' => true,
                            ],
                            'quantity' => 1,
                            'price' => [
                                'unitPrice' => 1,
                                'quantity' => 1,
                                'totalPrice' => 1,
                                'calculatedTaxes' => [],
                                'taxRules' => [],
                                'referencePrice' => null,
                                'listPrice' => null,
                                'regulationPrice' => null,
                                'extensions' => [],
                            ],
                            'identifier' => 'order-line-item-identifier',
                            'extensions' => [
                                'originalId' => [
                                    'id' => 'order-delivery-position-id-1',
                                    'extensions' => [],
                                ],
                            ],
                        ],
                    ],
                    'location' => [
                        'country' => [
                            'name' => 'country-name',
                            'iso' => null,
                            'position' => null,
                            'active' => null,
                            'shippingAvailable' => null,
                            'iso3' => null,
                            'displayStateInRegistration' => null,
                            'forceStateInRegistration' => null,
                            'checkVatIdPattern' => null,
                            'vatIdPattern' => null,
                            'vatIdRequired' => null,
                            'states' => null,
                            'translations' => null,
                            'orderAddresses' => null,
                            'customerAddresses' => null,
                            'salesChannelDefaultAssignments' => null,
                            'salesChannels' => null,
                            'taxRules' => null,
                            'currencyCountryRoundings' => null,
                            '_uniqueIdentifier' => 'country-id',
                            'versionId' => null,
                            'translated' => [],
                            'createdAt' => null,
                            'updatedAt' => null,
                            'extensions' => [],
                            'id' => 'country-id',
                            'customFields' => null,
                            'advancedPostalCodePattern' => null,
                            'defaultPostalCodePattern' => null,
                        ],
                        'state' => [
                            'countryId' => null,
                            'shortCode' => null,
                            'name' => 'country-state-name',
                            'position' => null,
                            'active' => null,
                            'country' => null,
                            'translations' => null,
                            'customerAddresses' => null,
                            'orderAddresses' => null,
                            '_uniqueIdentifier' => 'country-state-id',
                            'versionId' => null,
                            'translated' => [],
                            'createdAt' => null,
                            'updatedAt' => null,
                            'extensions' => [],
                            'id' => 'country-state-id',
                            'customFields' => null,
                        ],
                        'address' => null,
                        'extensions' => [],
                    ],
                    'shippingMethod' => [
                        'name' => null,
                        'active' => null,
                        'position' => null,
                        'description' => null,
                        'trackingUrl' => null,
                        'deliveryTimeId' => null,
                        'deliveryTime' => null,
                        'translations' => null,
                        'orderDeliveries' => null,
                        'salesChannelDefaultAssignments' => null,
                        'salesChannels' => null,
                        'availabilityRule' => null,
                        'availabilityRuleId' => null,
                        'prices' => [],
                        'mediaId' => null,
                        'taxId' => null,
                        'media' => null,
                        'tags' => null,
                        'taxType' => null,
                        'tax' => null,
                        '_uniqueIdentifier' => null,
                        'versionId' => null,
                        'translated' => [],
                        'createdAt' => null,
                        'updatedAt' => null,
                        'extensions' => [],
                        'id' => null,
                        'customFields' => null,
                        'appShippingMethod' => null,
                        'technicalName' => null,
                    ],
                    'shippingCosts' => [
                        'unitPrice' => 1,
                        'quantity' => 1,
                        'totalPrice' => 1,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                        'referencePrice' => null,
                        'listPrice' => null,
                        'regulationPrice' => null,
                        'extensions' => [],
                    ],
                    'extensions' => [
                        'originalId' => [
                            'id' => 'order-delivery-id',
                            'extensions' => [],
                        ],
                        'originalAddressId' => [
                            'id' => 'order-address-id',
                            'extensions' => [],
                        ],
                        'originalAddressVersionId' => [
                            'id' => 'order-address-version-id',
                            'extensions' => [],
                        ],
                    ],
                ],
            ],
            'transactions' => [],
            'modified' => false,
            'customerComment' => null,
            'affiliateCode' => null,
            'campaignCode' => null,
            'source' => null,
            'hash' => null,
            'states' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getExpectedConvertToOrder(): array
    {
        return [
            'price' => [
                'netPrice' => 0,
                'totalPrice' => 0,
                'calculatedTaxes' => [],
                'taxRules' => [],
                'positionPrice' => 0,
                'taxStatus' => 'gross',
                'rawTotal' => 0,
                'extensions' => [],
            ],
            'shippingCosts' => [
                'unitPrice' => 0,
                'quantity' => 1,
                'totalPrice' => 0,
                'calculatedTaxes' => [],
                'taxRules' => [],
                'referencePrice' => null,
                'listPrice' => null,
                'regulationPrice' => null,
                'extensions' => [],
            ],
            'currencyId' => 'b7d2554b0ce847cd82f3ac9bd1c0dfca',
            'currencyFactor' => 1,
            'salesChannelId' => TestDefaults::SALES_CHANNEL,
            'lineItems' => [
                [
                    'identifier' => 'line-item-id-1',
                    'quantity' => 1,
                    'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                    'label' => 'line-item-label-1',
                    'good' => true,
                    'removable' => false,
                    'stackable' => false,
                    'states' => [],
                    'position' => 1,
                    'price' => [
                        'unitPrice' => 1,
                        'quantity' => 1,
                        'totalPrice' => 1,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                        'referencePrice' => null,
                        'listPrice' => null,
                        'regulationPrice' => null,
                        'extensions' => [],
                    ],
                    'payload' => [],
                ],
                [
                    'identifier' => 'line-item-id-2',
                    'quantity' => 1,
                    'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                    'label' => 'line-item-label-2',
                    'good' => true,
                    'removable' => false,
                    'stackable' => false,
                    'states' => [],
                    'position' => 2,
                    'price' => [
                        'unitPrice' => 1,
                        'quantity' => 1,
                        'totalPrice' => 1,
                        'calculatedTaxes' => [],
                        'taxRules' => [],
                        'referencePrice' => null,
                        'listPrice' => null,
                        'regulationPrice' => null,
                        'extensions' => [],
                    ],
                    'payload' => [],
                ],
            ],
            'deliveries' => [[
                'positions' => [],
                'shippingCosts' => [
                    'calculatedTaxes' => [],
                    'extensions' => [],
                    'listPrice' => null,
                    'quantity' => 1,
                    'referencePrice' => null,
                    'regulationPrice' => null,
                    'taxRules' => [],
                    'totalPrice' => 1,
                    'unitPrice' => 1,
                ],
                'shippingMethodId' => 'shipping-method-id',
                'shippingOrderAddress' => [
                    'city' => 'billing-address-city',
                    'countryId' => 'billing-address-country-id',
                    'name' => 'billing-address-first-name',
                    'salutationId' => 'billing-address-salutation-id',
                    'street' => 'billing-address-street',
                    'zipcode' => 'billing-address-zipcode',
                ],
                'stateId' => '',
            ]],
            'customerComment' => null,
            'affiliateCode' => null,
            'campaignCode' => null,
            'source' => null,
            'createdById' => null,
            'itemRounding' => [
                'decimals' => 2,
                'extensions' => [],
                'interval' => 0.01,
                'roundForNet' => true,
            ],
            'totalRounding' => [
                'decimals' => 2,
                'extensions' => [],
                'interval' => 0.01,
                'roundForNet' => true,
            ],
            'orderCustomer' => [
                'company' => null,
                'customFields' => null,
                'customer' => [
                    'id' => 'customer-id',
                    'lastPaymentMethodId' => 'payment-method-id',
                ],
                'customerNumber' => 'customer-number',
                'email' => 'customer-email',
                'name' => 'customer-first-name',
                'remoteAddress' => null,
                'salutationId' => 'customer-salutation-id',
                'title' => null,
                'vatIds' => null,
            ],
            'transactions' => [],
            'orderNumber' => '10000',
            'ruleIds' => [
                'order-rule-id-1',
                'order-rule-id-2',
            ],
            'addresses' => [
                [
                    'city' => 'billing-address-city',
                    'countryId' => 'billing-address-country-id',
                    'name' => 'billing-address-first-name',
                    'salutationId' => 'billing-address-salutation-id',
                    'street' => 'billing-address-street',
                    'zipcode' => 'billing-address-zipcode',
                ],
            ],
        ];
    }
}
